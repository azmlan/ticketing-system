<?php

namespace App\Modules\Tickets\Services;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

class FileUploadService
{
    protected const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    protected const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    protected const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function store(UploadedFile $file, Ticket $ticket, User $uploader): TicketAttachment
    {
        $mimeType = $this->validateAndGetMime($file);

        if ($ticket->attachments()->count() >= 5) {
            throw new InvalidFileException('Maximum of 5 attachments per ticket.');
        }

        $ulid = (string) Str::ulid();
        $path = "tickets/{$ticket->id}/{$ulid}";

        ['mime' => $mimeType, 'size' => $size] = $this->processAndSave($file, $mimeType, $path);

        return TicketAttachment::create([
            'ticket_id'     => $ticket->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_size'     => $size,
            'mime_type'     => $mimeType,
            'uploaded_by'   => $uploader->id,
        ]);
    }

    /**
     * Validates size, MIME via magic bytes, allowed-list, and extension mismatch.
     * Returns the detected MIME type.
     */
    protected function validateAndGetMime(UploadedFile $file): string
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidFileException('File exceeds the 10 MB maximum.');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getPathname());

        if (! in_array($mimeType, static::ALLOWED_MIMES, true)) {
            throw new InvalidFileException("File type is not allowed.");
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, static::IMAGE_EXTENSIONS, true) && ! str_starts_with($mimeType, 'image/')) {
            throw new InvalidFileException('File content does not match the declared extension.');
        }

        return $mimeType;
    }

    /**
     * Processes (resize + re-encode for images) and stores to the given path.
     * Returns ['mime' => ..., 'size' => ...].
     */
    protected function processAndSave(UploadedFile $file, string $mimeType, string $path): array
    {
        if (str_starts_with($mimeType, 'image/')) {
            $image   = Image::decodePath($file->getPathname());
            $image->scaleDown(width: 2048, height: 2048);
            $encoded = $image->encode(new JpegEncoder(quality: 80));
            Storage::disk('local')->put($path, (string) $encoded);
            $mimeType = 'image/jpeg';
            $size     = Storage::disk('local')->size($path);
        } else {
            Storage::disk('local')->put($path, file_get_contents($file->getPathname()));
            $size = $file->getSize();
        }

        return ['mime' => $mimeType, 'size' => $size];
    }
}

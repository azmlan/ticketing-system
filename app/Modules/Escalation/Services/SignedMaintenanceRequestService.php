<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Cross-module Ticket import is permitted in Escalation services (designated seam).
class SignedMaintenanceRequestService
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function upload(string $ticketId, UploadedFile $file, User $actor): MaintenanceRequest
    {
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($ticketId);

        if ($actor->id !== $ticket->requester_id) {
            abort(403);
        }

        if ($ticket->status->value !== 'action_required') {
            abort(403);
        }

        $this->validateFile($file);

        return DB::transaction(function () use ($ticket, $file, $actor) {
            $ulid = (string) Str::ulid();
            $path = "maintenance-requests/{$ticket->id}/signed/{$ulid}";

            Storage::disk('local')->put($path, file_get_contents($file->getPathname()));

            $mr = MaintenanceRequest::where('ticket_id', $ticket->id)->firstOrFail();
            $mr->update([
                'submitted_file_path' => $path,
                'submitted_at'        => now(),
                'status'              => 'submitted',
            ]);

            app(TicketStateMachine::class)->transition($ticket, 'awaiting_final_approval', $actor);

            return $mr->fresh();
        });
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidFileException(__('escalation.validation.signed_file_too_large'));
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->getPathname());

        if (! in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new InvalidFileException(__('escalation.validation.signed_file_invalid_type'));
        }
    }
}

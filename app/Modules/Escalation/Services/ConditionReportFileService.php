<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use App\Modules\Tickets\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ConditionReportFileService extends FileUploadService
{
    public function storeForReport(UploadedFile $file, ConditionReport $report): ConditionReportAttachment
    {
        $mimeType = $this->validateAndGetMime($file);

        if ($report->attachments()->count() >= 5) {
            throw new InvalidFileException('Maximum of 5 attachments per condition report.');
        }

        $ulid = (string) Str::ulid();
        $path = "condition-reports/{$report->id}/{$ulid}";

        ['mime' => $mimeType, 'size' => $size] = $this->processAndSave($file, $mimeType, $path);

        return ConditionReportAttachment::create([
            'condition_report_id' => $report->id,
            'original_name'       => $file->getClientOriginalName(),
            'file_path'           => $path,
            'file_size'           => $size,
            'mime_type'           => $mimeType,
        ]);
    }
}

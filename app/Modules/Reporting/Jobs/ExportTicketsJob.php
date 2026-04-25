<?php

namespace App\Modules\Reporting\Jobs;

use App\Modules\Reporting\Models\TicketExport;
use App\Modules\Reporting\Notifications\ExportReadyNotification;
use App\Modules\Reporting\Services\ExportService;
use App\Modules\Reporting\Writers\XlsxWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $exportId,
    ) {}

    public function handle(ExportService $exportService): void
    {
        $export = TicketExport::findOrFail($this->exportId);

        app()->setLocale($export->locale);

        $headers = $exportService->exportHeaders($export->include_csat);
        $rows = $exportService->exportRows($export->filters ?? [], $export->include_csat);

        $filename = 'exports/'.$export->id.'.'.$export->format;

        if ($export->format === 'xlsx') {
            $this->writeXlsxToStorage($filename, $headers, $rows);
        } else {
            $this->writeCsvToStorage($filename, $headers, $rows);
        }

        $export->update([
            'file_path' => $filename,
            'status' => 'ready',
            'expires_at' => now()->addDay(),
        ]);

        $downloadUrl = route('reports.exports.download', $export->id);

        $export->user->notify(new ExportReadyNotification($export, $downloadUrl));
    }

    public function failed(\Throwable $e): void
    {
        TicketExport::where('id', $this->exportId)->update(['status' => 'failed']);
    }

    private function writeCsvToStorage(string $path, array $headers, array $rows): void
    {
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        fwrite($tempFile, "\xEF\xBB\xBF");
        fputcsv($tempFile, $headers);
        foreach ($rows as $row) {
            fputcsv($tempFile, $row);
        }
        rewind($tempFile);

        Storage::disk('local')->put($path, stream_get_contents($tempFile));
        fclose($tempFile);
    }

    private function writeXlsxToStorage(string $path, array $headers, array $rows): void
    {
        $writer = new XlsxWriter;
        $temp = $writer->writeTempFile($headers, $rows);

        Storage::disk('local')->put($path, file_get_contents($temp));
        @unlink($temp);
    }
}

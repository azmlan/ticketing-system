<?php

namespace App\Modules\Reporting\Controllers;

use App\Modules\Reporting\Models\TicketExport;
use App\Modules\Reporting\Services\ExportService;
use App\Modules\Reporting\Writers\CsvWriter;
use App\Modules\Reporting\Writers\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function export(Request $request, ExportService $exportService): mixed
    {
        abort_unless(
            auth()->user()->is_super_user || auth()->user()->hasPermission('system.view-reports'),
            403
        );

        $filters = array_filter([
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'category_id' => $request->query('category_id'),
            'priority' => $request->query('priority'),
            'group_id' => $request->query('group_id'),
            'tech_id' => $request->query('tech_id'),
            'status' => $request->query('status'),
        ]);

        $includeCsat = $this->userCanSeeCsat();
        $format = $request->query('format', 'csv');
        $headers = $exportService->exportHeaders($includeCsat);
        $rows = $exportService->exportRows($filters, $includeCsat);
        $filename = 'tickets-export-'.now()->format('Y-m-d');

        if ($format === 'xlsx') {
            return (new XlsxWriter)->download($headers, $rows, $filename.'.xlsx');
        }

        return (new CsvWriter)->download($headers, $rows, $filename.'.csv');
    }

    public function download(string $exportId): mixed
    {
        $export = TicketExport::findOrFail($exportId);

        abort_unless($export->user_id === auth()->id(), 403);

        if (! $export->file_path || ! Storage::disk('local')->exists($export->file_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($export->file_path);
        $extension = $export->format;
        $mimeType = $extension === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';
        $filename = 'tickets-export.'.$extension;

        return response()->download($path, $filename, ['Content-Type' => $mimeType])
            ->deleteFileAfterSend(true);
    }

    private function userCanSeeCsat(): bool
    {
        $user = auth()->user();

        return $user->is_super_user || $user->hasPermission('ticket.view-all');
    }
}

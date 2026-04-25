<?php

namespace App\Modules\Reporting\Controllers;

use App\Modules\Reporting\Services\ExportService;
use App\Modules\Reporting\Writers\CsvWriter;
use App\Modules\Reporting\Writers\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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

        $format = $request->query('format', 'csv');
        $headers = $exportService->exportHeaders();
        $rows = $exportService->exportRows($filters);
        $filename = 'tickets-export-'.now()->format('Y-m-d');

        if ($format === 'xlsx') {
            return (new XlsxWriter)->download($headers, $rows, $filename.'.xlsx');
        }

        return (new CsvWriter)->download($headers, $rows, $filename.'.csv');
    }
}

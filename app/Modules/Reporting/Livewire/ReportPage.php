<?php

namespace App\Modules\Reporting\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Reporting\Jobs\ExportTicketsJob;
use App\Modules\Reporting\Models\TicketExport;
use App\Modules\Reporting\Services\ReportService;
use App\Modules\Shared\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportPage extends Component
{
    public string $reportType = 'ticket_volume';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $categoryId = '';

    public string $priority = '';

    public string $groupId = '';

    public string $techId = '';

    public string $status = '';

    public bool $exportQueued = false;

    public function mount(): void
    {
        abort_unless(
            auth()->user()->is_super_user || auth()->user()->hasPermission('system.view-reports'),
            403
        );

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updatedReportType(): void {}

    public function updatedCategoryId(): void {}

    public function updatedPriority(): void {}

    public function updatedGroupId(): void {}

    public function updatedTechId(): void {}

    public function updatedStatus(): void {}

    public function resetFilters(): void
    {
        $this->categoryId = '';
        $this->priority = '';
        $this->groupId = '';
        $this->techId = '';
        $this->status = '';
    }

    public function queueExport(string $format): void
    {
        abort_unless(
            auth()->user()->is_super_user || auth()->user()->hasPermission('system.view-reports'),
            403
        );

        $user = auth()->user();
        $includeCsat = $user->is_super_user || $user->hasPermission('ticket.view-all');

        $export = TicketExport::create([
            'user_id' => $user->id,
            'format' => in_array($format, ['csv', 'xlsx']) ? $format : 'csv',
            'filters' => array_filter($this->buildFilters()),
            'locale' => $user->locale ?? 'ar',
            'include_csat' => $includeCsat,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        ExportTicketsJob::dispatch($export->id);

        $this->exportQueued = true;
    }

    public function render(ReportService $reportService)
    {
        $rows = collect();
        $headers = [];

        if ($this->dateFrom && $this->dateTo && $this->dateTo >= $this->dateFrom) {
            $rows = $reportService->run($this->reportType, $this->buildFilters());
            $headers = $reportService->headers($this->reportType);
        }

        $exportParams = array_filter($this->buildFilters());

        return view('livewire.reports.report-page', [
            'rows' => $rows,
            'headers' => $headers,
            'reportTypes' => $reportService->types(),
            'categories' => Category::where('is_active', true)->whereNull('deleted_at')->orderBy('name_en')->get(),
            'groups' => Group::where('is_active', true)->whereNull('deleted_at')->orderBy('name_en')->get(),
            'techs' => User::where('is_tech', true)->orderBy('full_name')->get(),
            'csvExportUrl' => route('reports.export', array_merge($exportParams, ['format' => 'csv'])),
            'xlsxExportUrl' => route('reports.export', array_merge($exportParams, ['format' => 'xlsx'])),
        ]);
    }

    public function buildFilters(): array
    {
        return [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'category_id' => $this->categoryId ?: null,
            'priority' => $this->priority ?: null,
            'group_id' => $this->groupId ?: null,
            'tech_id' => $this->techId ?: null,
            'status' => $this->status ?: null,
        ];
    }
}

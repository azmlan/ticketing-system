<?php

namespace App\Modules\Escalation\Livewire;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Services\ConditionReportApprovalService;
use App\Modules\Tickets\Services\RichTextSanitizer;
use Livewire\Component;

class ReviewConditionReport extends Component
{
    public string $ticketId = '';
    public string $conditionReportId = '';
    public string $reviewNotes = '';
    public bool $showRejectForm = false;

    public function mount(string $ticketId): void
    {
        $user = auth()->user();

        if (! $user || (! $user->is_super_user && ! $user->hasPermission('escalation.approve'))) {
            abort(403);
        }

        $report = ConditionReport::where('ticket_id', $ticketId)
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        $this->ticketId          = $ticketId;
        $this->conditionReportId = $report->id;
    }

    public function approve(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $report = ConditionReport::findOrFail($this->conditionReportId);

        app(ConditionReportApprovalService::class)->approve($report, $user);

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function reject(): void
    {
        $this->validate([
            'reviewNotes' => ['required', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        $report = ConditionReport::findOrFail($this->conditionReportId);

        app(ConditionReportApprovalService::class)->reject($report, $user, $this->reviewNotes);

        $this->redirect(route('tickets.show', $this->ticketId), navigate: true);
    }

    public function render()
    {
        $report    = ConditionReport::with(['tech', 'location', 'attachments'])->findOrFail($this->conditionReportId);
        $sanitizer = app(RichTextSanitizer::class);

        return view('livewire.escalation.review-condition-report', [
            'report'            => $report,
            'currentCondition'  => $sanitizer->sanitize($report->current_condition),
            'conditionAnalysis' => $sanitizer->sanitize($report->condition_analysis),
            'requiredAction'    => $sanitizer->sanitize($report->required_action),
        ]);
    }
}

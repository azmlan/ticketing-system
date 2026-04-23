<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Services\RichTextSanitizer;
use App\Modules\Tickets\Services\TicketStateMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

// Escalation service is the only Escalation code that imports Ticket model.
// It is the designated seam where Escalation triggers Ticket state transitions.
class ConditionReportService
{
    public function __construct(
        private readonly RichTextSanitizer $sanitizer,
        private readonly ConditionReportFileService $fileService,
        private readonly TicketStateMachine $stateMachine,
    ) {}

    /**
     * @param  UploadedFile[]  $files
     */
    public function submit(string $ticketId, array $data, array $files, User $actor): ConditionReport
    {
        if (! $actor->is_tech) {
            abort(403);
        }

        $ticket = Ticket::withoutGlobalScopes()->where('id', $ticketId)->firstOrFail();

        if ($ticket->status->value !== 'in_progress') {
            abort(403);
        }

        return DB::transaction(function () use ($ticket, $data, $files, $actor) {
            $report = ConditionReport::create([
                'ticket_id'          => $ticket->id,
                'report_type'        => $data['reportType'],
                'location_id'        => $data['locationId'] ?: null,
                'report_date'        => now()->toDateString(),
                'current_condition'  => $this->sanitizer->sanitize($data['currentCondition']),
                'condition_analysis' => $this->sanitizer->sanitize($data['conditionAnalysis']),
                'required_action'    => $this->sanitizer->sanitize($data['requiredAction']),
                'tech_id'            => $actor->id,
                'status'             => 'pending',
            ]);

            foreach ($files as $file) {
                $this->fileService->storeForReport($file, $report);
            }

            $this->stateMachine->transition($ticket, 'awaiting_approval', $actor);

            return $report;
        });
    }
}

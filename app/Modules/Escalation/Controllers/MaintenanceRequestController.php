<?php

namespace App\Modules\Escalation\Controllers;

use App\Modules\Escalation\Services\MaintenanceRequestService;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private readonly MaintenanceRequestService $service,
    ) {}

    public function download(Request $request, string $ticketId, string $locale)
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            abort(404);
        }

        $ticket = Ticket::withoutGlobalScopes()->findOrFail($ticketId);
        $user   = $request->user();

        $authorized = $user->id === $ticket->requester_id
            || $user->id === $ticket->assigned_to
            || $user->is_super_user
            || $user->hasPermission('escalation.approve');

        if (! $authorized) {
            abort(403);
        }

        $maintenanceRequest = $this->service->generate($ticketId, $locale);

        return Storage::disk('local')->download(
            $maintenanceRequest->generated_file_path,
            "maintenance-request-{$locale}.docx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }
}

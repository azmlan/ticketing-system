<?php

namespace App\Modules\Escalation\Controllers;

use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

// Cross-module Ticket import is permitted in Escalation controllers (designated seam).
class SignedDocumentController extends Controller
{
    public function show(Request $request, MaintenanceRequest $maintenanceRequest): mixed
    {
        $user   = $request->user();
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($maintenanceRequest->ticket_id);

        $canView = $user->id === $ticket->requester_id
            || $user->id === $ticket->assigned_to
            || $user->is_super_user
            || $user->hasPermission('escalation.approve');

        if (! $canView) {
            abort(403);
        }

        if (! $maintenanceRequest->submitted_file_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($maintenanceRequest->submitted_file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($maintenanceRequest->submitted_file_path);
    }
}

<?php

namespace App\Modules\Tickets\Controllers;

use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(Request $request, string $ticketId, TicketAttachment $attachment): Response
    {
        // Bypass EmployeeTicketScope so an unauthorized user gets 403, not 404
        $ticket = Ticket::withoutGlobalScopes()->findOrFail($ticketId);

        if ($attachment->ticket_id !== $ticket->id) {
            abort(404);
        }

        Gate::authorize('view', $attachment);

        $content = Storage::disk('local')->get($attachment->file_path);

        return response($content, 200, [
            'Content-Type'        => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }
}

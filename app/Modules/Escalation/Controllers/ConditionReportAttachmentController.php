<?php

namespace App\Modules\Escalation\Controllers;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ConditionReportAttachmentController extends Controller
{
    public function show(Request $request, string $conditionReportId, ConditionReportAttachment $attachment): Response
    {
        $report = ConditionReport::findOrFail($conditionReportId);

        if ($attachment->condition_report_id !== $report->id) {
            abort(404);
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $authorized = $user->is_super_user
            || $user->hasPermission('ticket.view-all')
            || $user->hasPermission('escalation.approve')
            || $user->id === $report->tech_id;

        if (! $authorized) {
            abort(403);
        }

        $content = Storage::disk('local')->get($attachment->file_path);

        return response($content, 200, [
            'Content-Type'        => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }
}

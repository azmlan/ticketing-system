<?php

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\ConditionReportAttachment;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;

// ─── ConditionReport ─────────────────────────────────────────────────────────

it('ConditionReport factory creates a valid DB record', function () {
    $report = ConditionReport::factory()->create();

    expect($report->id)->toHaveLength(26)
        ->and($report->status)->toBe('pending')
        ->and($report->reviewed_by)->toBeNull()
        ->and($report->reviewed_at)->toBeNull();
});

it('ConditionReport approved state sets status, reviewed_by, and reviewed_at', function () {
    $report = ConditionReport::factory()->approved()->create();

    expect($report->status)->toBe('approved')
        ->and($report->reviewed_by)->not->toBeNull()
        ->and($report->reviewed_at)->not->toBeNull();
});

it('ConditionReport rejected state sets status and reviewed_by', function () {
    $report = ConditionReport::factory()->rejected()->create();

    expect($report->status)->toBe('rejected')
        ->and($report->reviewed_by)->not->toBeNull();
});

it('ConditionReport fillable columns round-trip correctly', function () {
    $tech = User::factory()->tech()->create();
    $ticket = Ticket::factory()->create();

    $report = ConditionReport::factory()->create([
        'ticket_id'          => $ticket->id,
        'report_type'        => 'hardware',
        'current_condition'  => 'Device overheats',
        'condition_analysis' => 'Fan failure',
        'required_action'    => 'Replace fan unit',
        'tech_id'            => $tech->id,
        'status'             => 'pending',
    ]);

    $fresh = ConditionReport::find($report->id);

    expect($fresh->ticket_id)->toBe($ticket->id)
        ->and($fresh->report_type)->toBe('hardware')
        ->and($fresh->current_condition)->toBe('Device overheats')
        ->and($fresh->condition_analysis)->toBe('Fan failure')
        ->and($fresh->required_action)->toBe('Replace fan unit')
        ->and($fresh->tech_id)->toBe($tech->id)
        ->and($fresh->status)->toBe('pending');
});

it('ConditionReport tech relationship resolves correctly', function () {
    $tech = User::factory()->tech()->create();
    $report = ConditionReport::factory()->create(['tech_id' => $tech->id]);

    expect($report->tech->id)->toBe($tech->id);
});

it('ConditionReport reviewer relationship resolves when set', function () {
    $reviewer = User::factory()->create();
    $report = ConditionReport::factory()->approved()->create(['reviewed_by' => $reviewer->id]);

    expect($report->reviewer->id)->toBe($reviewer->id);
});

it('ConditionReport has no deleted_at (no SoftDeletes)', function () {
    $report = ConditionReport::factory()->create();

    expect(array_key_exists('deleted_at', $report->getAttributes()))->toBeFalse();
});

// ─── ConditionReportAttachment ───────────────────────────────────────────────

it('ConditionReportAttachment factory creates a record linked to a condition report', function () {
    $report = ConditionReport::factory()->create();
    $attachment = ConditionReportAttachment::factory()->create([
        'condition_report_id' => $report->id,
    ]);

    expect($attachment->id)->toHaveLength(26)
        ->and($attachment->condition_report_id)->toBe($report->id);
});

it('ConditionReportAttachment conditionReport relationship resolves', function () {
    $report = ConditionReport::factory()->create();
    $attachment = ConditionReportAttachment::factory()->create([
        'condition_report_id' => $report->id,
    ]);

    expect($attachment->conditionReport->id)->toBe($report->id);
});

it('ConditionReport attachments hasMany resolves', function () {
    $report = ConditionReport::factory()->create();
    ConditionReportAttachment::factory()->count(2)->create([
        'condition_report_id' => $report->id,
    ]);

    expect($report->attachments)->toHaveCount(2);
});

it('ConditionReportAttachment fillable columns round-trip', function () {
    $report = ConditionReport::factory()->create();

    $attachment = ConditionReportAttachment::factory()->create([
        'condition_report_id' => $report->id,
        'original_name'       => 'photo.jpg',
        'file_path'           => 'escalation/abc/123.jpg',
        'file_size'           => 204800,
        'mime_type'           => 'image/jpeg',
    ]);

    $fresh = ConditionReportAttachment::find($attachment->id);

    expect($fresh->original_name)->toBe('photo.jpg')
        ->and($fresh->file_path)->toBe('escalation/abc/123.jpg')
        ->and($fresh->file_size)->toBe(204800)
        ->and($fresh->mime_type)->toBe('image/jpeg');
});

// ─── MaintenanceRequest ──────────────────────────────────────────────────────

it('MaintenanceRequest factory creates a valid record with status pending', function () {
    $req = MaintenanceRequest::factory()->create();

    expect($req->id)->toHaveLength(26)
        ->and($req->status)->toBe('pending')
        ->and($req->rejection_count)->toBe(0)
        ->and($req->submitted_file_path)->toBeNull();
});

it('MaintenanceRequest submitted state sets status and submitted_at', function () {
    $req = MaintenanceRequest::factory()->submitted()->create();

    expect($req->status)->toBe('submitted')
        ->and($req->submitted_file_path)->not->toBeNull()
        ->and($req->submitted_at)->not->toBeNull();
});

it('MaintenanceRequest approved state sets reviewed_by', function () {
    $req = MaintenanceRequest::factory()->approved()->create();

    expect($req->status)->toBe('approved')
        ->and($req->reviewed_by)->not->toBeNull();
});

it('MaintenanceRequest rejected state increments rejection_count', function () {
    $req = MaintenanceRequest::factory()->rejected()->create();

    expect($req->status)->toBe('rejected')
        ->and($req->rejection_count)->toBeGreaterThan(0);
});

it('MaintenanceRequest fillable columns round-trip correctly', function () {
    $ticket = Ticket::factory()->create();

    $req = MaintenanceRequest::factory()->create([
        'ticket_id'           => $ticket->id,
        'generated_file_path' => 'maintenance/abc.docx',
        'generated_locale'    => 'ar',
        'status'              => 'pending',
        'rejection_count'     => 0,
    ]);

    $fresh = MaintenanceRequest::find($req->id);

    expect($fresh->ticket_id)->toBe($ticket->id)
        ->and($fresh->generated_file_path)->toBe('maintenance/abc.docx')
        ->and($fresh->generated_locale)->toBe('ar')
        ->and($fresh->status)->toBe('pending')
        ->and($fresh->rejection_count)->toBe(0);
});

it('MaintenanceRequest reviewer relationship resolves when set', function () {
    $reviewer = User::factory()->create();
    $req = MaintenanceRequest::factory()->approved()->create(['reviewed_by' => $reviewer->id]);

    expect($req->reviewer->id)->toBe($reviewer->id);
});

it('MaintenanceRequest has no deleted_at (no SoftDeletes)', function () {
    $req = MaintenanceRequest::factory()->create();

    expect(array_key_exists('deleted_at', $req->getAttributes()))->toBeFalse();
});

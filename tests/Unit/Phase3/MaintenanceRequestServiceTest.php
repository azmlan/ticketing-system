<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Escalation\Services\MaintenanceRequestService;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeSvcTicket(?User $tech = null, ?User $requester = null): Ticket
{
    $tech      ??= User::factory()->tech()->create();
    $requester ??= User::factory()->create();
    $group       = Group::factory()->create();
    $category    = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-' . str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT),
        'subject'         => 'Unit test ticket',
        'description'     => '<p>This is <strong>bold</strong> text with <em>emphasis</em>.</p>',
        'status'          => TicketStatus::ActionRequired,
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => $requester->id,
        'assigned_to'     => $tech->id,
        'incident_origin' => 'web',
    ]);
}

function makeSvcApprovedCr(Ticket $ticket, User $tech): ConditionReport
{
    return ConditionReport::create([
        'ticket_id'          => $ticket->id,
        'report_type'        => 'Hardware failure',
        'location_id'        => null,
        'report_date'        => now()->toDateString(),
        'current_condition'  => '<p>Screen <strong>cracked</strong>.</p>',
        'condition_analysis' => '<p>Physical damage.</p>',
        'required_action'    => '<p>Replace screen.</p>',
        'tech_id'            => $tech->id,
        'status'             => 'approved',
    ]);
}

function readDocxXml(string $storagePath): string
{
    $content  = Storage::disk('local')->get($storagePath);
    $tempFile = tempnam(sys_get_temp_dir(), 'test_mreq_') . '.docx';
    file_put_contents($tempFile, $content);

    $zip = new ZipArchive();
    $zip->open($tempFile);
    $xml = (string) $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($tempFile);

    return $xml;
}

// ─── generate() — locale validation ──────────────────────────────────────────

it('generate() with invalid locale throws InvalidArgumentException', function () {
    expect(fn () => app(MaintenanceRequestService::class)->generate('01j0000000000000000000000', 'fr'))
        ->toThrow(\InvalidArgumentException::class, "Invalid locale 'fr'");
});

it('generate() with empty locale throws InvalidArgumentException', function () {
    expect(fn () => app(MaintenanceRequestService::class)->generate('01j0000000000000000000000', ''))
        ->toThrow(\InvalidArgumentException::class);
});

// ─── generate() — AR locale ───────────────────────────────────────────────────

it('generate() with locale=ar produces a file on disk at the expected path', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeSvcTicket($tech);
    makeSvcApprovedCr($ticket, $tech);

    $result = app(MaintenanceRequestService::class)->generate($ticket->id, 'ar');

    expect($result)->toBeInstanceOf(MaintenanceRequest::class)
        ->and($result->generated_locale)->toBe('ar')
        ->and($result->generated_file_path)->not->toBeNull()
        ->and($result->generated_file_path)->toStartWith("maintenance-requests/{$ticket->id}/");

    Storage::disk('local')->assertExists($result->generated_file_path);
});

it('generate() with locale=ar updates the maintenance_request record', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeSvcTicket($tech);
    makeSvcApprovedCr($ticket, $tech);

    // Pre-create a pending record (as the listener would)
    MaintenanceRequest::create(['ticket_id' => $ticket->id, 'status' => 'pending']);

    $result = app(MaintenanceRequestService::class)->generate($ticket->id, 'ar');

    expect(MaintenanceRequest::where('ticket_id', $ticket->id)->count())->toBe(1)
        ->and($result->generated_locale)->toBe('ar')
        ->and($result->generated_file_path)->not->toBeNull();
});

// ─── generate() — EN locale ───────────────────────────────────────────────────

it('generate() with locale=en produces a file on disk', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeSvcTicket($tech);
    makeSvcApprovedCr($ticket, $tech);

    $result = app(MaintenanceRequestService::class)->generate($ticket->id, 'en');

    expect($result->generated_locale)->toBe('en')
        ->and($result->generated_file_path)->toStartWith("maintenance-requests/{$ticket->id}/");

    Storage::disk('local')->assertExists($result->generated_file_path);
});

it('generate() AR and EN produce different file paths', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeSvcTicket($tech);
    makeSvcApprovedCr($ticket, $tech);

    $ar = app(MaintenanceRequestService::class)->generate($ticket->id, 'ar');
    $en = app(MaintenanceRequestService::class)->generate($ticket->id, 'en');

    expect($ar->generated_file_path)->not->toBe($en->generated_file_path);
});

// ─── HTML stripping ───────────────────────────────────────────────────────────

it('generate() strips HTML tags from ticket description before inserting into DOCX', function () {
    $tech   = User::factory()->tech()->create();
    $ticket = makeSvcTicket($tech); // description has <p>, <strong>, <em>
    makeSvcApprovedCr($ticket, $tech); // condition fields have <p>, <strong>

    $result = app(MaintenanceRequestService::class)->generate($ticket->id, 'en');

    $xml = readDocxXml($result->generated_file_path);

    // DOCX XML uses <w:p> not raw HTML <p> tags
    expect($xml)->not->toContain('<p>')
        ->not->toContain('<strong>')
        ->not->toContain('<em>');
});

<?php

use App\Modules\CSAT\Models\CsatRating;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeExportManager(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

// ─── Access control ────────────────────────────────────────────────────────────

it('returns 403 for unauthenticated users', function () {
    $this->get(route('reports.export'))->assertRedirect('/login');
});

it('returns 403 for employees without permission', function () {
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);

    $this->actingAs($user)->get(route('reports.export'))->assertForbidden();
});

it('allows IT manager with permission to export', function () {
    $manager = makeExportManager();

    $this->actingAs($manager)->get(route('reports.export'))->assertOk();
});

it('allows super user to export', function () {
    $super = User::factory()->create(['is_super_user' => true]);

    $this->actingAs($super)->get(route('reports.export'))->assertOk();
});

// ─── CSV format ────────────────────────────────────────────────────────────────

it('returns csv content-type header', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

it('csv contains standard column headers', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];
    $headers = str_getcsv($firstLine);

    expect($headers)->toContain(__('reports.export.ticket_number'));
    expect($headers)->toContain(__('reports.export.subject'));
    expect($headers)->toContain(__('reports.export.status'));
    expect($headers)->toContain(__('reports.export.priority'));
    expect($headers)->toContain(__('reports.export.category'));
    expect($headers)->toContain(__('reports.export.subcategory'));
    expect($headers)->toContain(__('reports.export.group'));
    expect($headers)->toContain(__('reports.export.assigned_tech'));
    expect($headers)->toContain(__('reports.export.requester'));
    expect($headers)->toContain(__('reports.export.created_at'));
    expect($headers)->toContain(__('reports.export.resolved_at'));
    expect($headers)->toContain(__('reports.export.closed_at'));
});

it('csv contains sla column headers', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];
    $headers = str_getcsv($firstLine);

    expect($headers)->toContain(__('reports.export.sla_response_target_mins'));
    expect($headers)->toContain(__('reports.export.sla_response_actual_mins'));
    expect($headers)->toContain(__('reports.export.sla_response_status'));
    expect($headers)->toContain(__('reports.export.sla_resolution_target_mins'));
    expect($headers)->toContain(__('reports.export.sla_resolution_actual_mins'));
    expect($headers)->toContain(__('reports.export.sla_resolution_status'));
    expect($headers)->toContain(__('reports.export.sla_total_paused_mins'));
});

it('csv contains csat column headers', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];
    $headers = str_getcsv($firstLine);

    expect($headers)->toContain(__('reports.export.csat_rating'));
    expect($headers)->toContain(__('reports.export.csat_comment'));
    expect($headers)->toContain(__('reports.export.csat_submitted_at'));
    expect($headers)->toContain(__('reports.export.csat_status'));
});

// ─── CSV data rows ─────────────────────────────────────────────────────────────

it('csv includes one data row per ticket', function () {
    $manager = makeExportManager();
    Ticket::factory()->count(3)->create();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    $lines = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));
    // first line = header, rest = data
    expect(count($lines) - 1)->toBe(3);
});

it('csv row contains correct ticket display number', function () {
    $manager = makeExportManager();
    $ticket  = Ticket::factory()->create();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    expect($content)->toContain($ticket->display_number);
});

it('csv row contains translated status', function () {
    $manager = makeExportManager();
    Ticket::factory()->create(['status' => 'in_progress']);

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    expect($content)->toContain(__('tickets.status.in_progress'));
});

it('csv includes sla data when ticket has sla record', function () {
    $manager = makeExportManager();
    $ticket  = Ticket::factory()->create();
    TicketSla::factory()->create([
        'ticket_id'                => $ticket->id,
        'response_target_minutes'  => 60,
        'response_elapsed_minutes' => 45,
        'response_status'          => 'on_track',
    ]);

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    expect($content)->toContain('60');
    expect($content)->toContain('45');
    expect($content)->toContain(__('reports.export.sla_statuses.on_track'));
});

it('csv includes csat data when ticket has submitted rating', function () {
    $manager   = makeExportManager();
    $tech      = User::factory()->tech()->create();
    $requester = User::factory()->create(['is_tech' => false]);
    $ticket    = Ticket::factory()->create(['assigned_to' => $tech->id]);

    CsatRating::factory()->submitted()->create([
        'ticket_id'    => $ticket->id,
        'tech_id'      => $tech->id,
        'requester_id' => $requester->id,
        'rating'       => 4,
        'comment'      => 'Great service',
    ]);

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));

    $content = $response->streamedContent();
    expect($content)->toContain('4');
    expect($content)->toContain('Great service');
    expect($content)->toContain(__('reports.export.csat_statuses.submitted'));
});

// ─── Filter application ────────────────────────────────────────────────────────

it('csv export respects status filter', function () {
    $manager = makeExportManager();
    Ticket::factory()->create(['status' => 'in_progress']);
    Ticket::factory()->create(['status' => 'resolved']);

    $responseAll = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv']));
    $responseFiltered = $this->actingAs($manager)->get(route('reports.export', ['format' => 'csv', 'status' => 'in_progress']));

    $allLines = array_values(array_filter(explode("\n", ltrim($responseAll->streamedContent(), "\xEF\xBB\xBF"))));
    $filteredLines = array_values(array_filter(explode("\n", ltrim($responseFiltered->streamedContent(), "\xEF\xBB\xBF"))));

    expect(count($allLines))->toBeGreaterThan(count($filteredLines));
    expect(count($filteredLines))->toBe(2); // header + 1 row
});

it('csv export respects date filter', function () {
    $manager = makeExportManager();
    Ticket::factory()->create(['created_at' => now()]);
    Ticket::factory()->create(['created_at' => now()->subYear()]);

    $response = $this->actingAs($manager)->get(route('reports.export', [
        'format'    => 'csv',
        'date_from' => now()->startOfMonth()->toDateString(),
        'date_to'   => now()->toDateString(),
    ]));

    $content = $response->streamedContent();
    $lines = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));
    expect(count($lines))->toBe(2); // header + 1 matching ticket
});

// ─── XLSX format ───────────────────────────────────────────────────────────────

it('returns xlsx content-type when format=xlsx', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'xlsx']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('xlsx filename has xlsx extension', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export', ['format' => 'xlsx']));

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('.xlsx');
});

// ─── Default format ────────────────────────────────────────────────────────────

it('defaults to csv when format is not specified', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

// ─── Export URLs present in report page view ──────────────────────────────────

it('report page view exposes csv and xlsx export links', function () {
    $manager = makeExportManager();

    $response = $this->actingAs($manager)->get(route('reports.index'));

    $response->assertOk();
    $response->assertSee('/reports/export', false);
    $response->assertSee('format=csv', false);
    $response->assertSee('format=xlsx', false);
});

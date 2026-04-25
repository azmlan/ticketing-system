<?php

use App\Modules\Reporting\Jobs\ExportTicketsJob;
use App\Modules\Reporting\Models\TicketExport;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeItManager(): User
{
    $user = User::factory()->create(['is_super_user' => true]);

    return $user;
}

function makeNonManagerWithReports(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

function csvHeadersFromStorage(TicketExport $export): array
{
    $content = Storage::disk('local')->get($export->file_path);
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];

    return str_getcsv($firstLine);
}

// ─── CSAT column visibility ────────────────────────────────────────────────────

it('csv export includes CSAT columns for IT manager', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    $manager = makeItManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => true,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $headers = csvHeadersFromStorage($export);

    expect($headers)->toContain(__('reports.export.csat_rating'));
    expect($headers)->toContain(__('reports.export.csat_comment'));
    expect($headers)->toContain(__('reports.export.csat_submitted_at'));
    expect($headers)->toContain(__('reports.export.csat_status'));
});

it('csv export omits CSAT columns for non-manager user', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    $user = makeNonManagerWithReports();

    $export = TicketExport::create([
        'user_id' => $user->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $headers = csvHeadersFromStorage($export);

    expect($headers)->not->toContain(__('reports.export.csat_rating'));
    expect($headers)->not->toContain(__('reports.export.csat_comment'));
    expect($headers)->not->toContain(__('reports.export.csat_status'));
});

it('synchronous export omits CSAT columns for non-manager', function () {
    app()->setLocale('en');

    $user = makeNonManagerWithReports();

    $response = $this->actingAs($user)
        ->get(route('reports.export', ['format' => 'csv']));

    $response->assertOk();
    $content = $response->streamedContent();
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];
    $headers = str_getcsv($firstLine);

    expect($headers)->not->toContain(__('reports.export.csat_rating'));
});

it('synchronous export includes CSAT columns for super user', function () {
    app()->setLocale('en');

    $super = User::factory()->create(['is_super_user' => true]);

    $response = $this->actingAs($super)
        ->get(route('reports.export', ['format' => 'csv']));

    $response->assertOk();
    $content = $response->streamedContent();
    $firstLine = explode("\n", ltrim($content, "\xEF\xBB\xBF"))[0];
    $headers = str_getcsv($firstLine);

    expect($headers)->toContain(__('reports.export.csat_rating'));
});

// ─── SLA columns ───────────────────────────────────────────────────────────────

it('queued csv export always includes SLA columns', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    $user = makeNonManagerWithReports();

    $export = TicketExport::create([
        'user_id' => $user->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $headers = csvHeadersFromStorage($export);

    expect($headers)->toContain(__('reports.export.sla_response_target_mins'));
    expect($headers)->toContain(__('reports.export.sla_resolution_status'));
    expect($headers)->toContain(__('reports.export.sla_total_paused_mins'));
});

// ─── Dynamic custom field columns ─────────────────────────────────────────────

it('queued csv export includes custom field columns for fields with values', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    if (! Schema::hasTable('custom_fields') || ! Schema::hasTable('ticket_custom_field_values')) {
        $this->markTestSkipped('custom_fields tables not present');
    }

    $user = makeNonManagerWithReports();
    $ticket = Ticket::factory()->create();

    $fieldId = DB::table('custom_fields')->insertGetId([
        'id' => Str::ulid(),
        'name_ar' => 'رقم الأصل',
        'name_en' => 'Asset Number',
        'field_type' => 'text',
        'is_required' => false,
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fieldUlid = DB::table('custom_fields')->find($fieldId)?->id
        ?? DB::table('custom_fields')->orderByDesc('created_at')->value('id');

    DB::table('ticket_custom_field_values')->insert([
        'id' => Str::ulid(),
        'ticket_id' => $ticket->id,
        'custom_field_id' => $fieldUlid,
        'value' => 'ASSET-001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $export = TicketExport::create([
        'user_id' => $user->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $headers = csvHeadersFromStorage($export);

    expect($headers)->toContain('Asset Number');

    $content = Storage::disk('local')->get($export->file_path);
    expect($content)->toContain('ASSET-001');
});

// ─── Standard columns ─────────────────────────────────────────────────────────

it('queued csv export includes all standard columns', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    $user = makeNonManagerWithReports();

    $export = TicketExport::create([
        'user_id' => $user->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $headers = csvHeadersFromStorage($export);

    expect($headers)->toContain(__('reports.export.ticket_number'));
    expect($headers)->toContain(__('reports.export.subject'));
    expect($headers)->toContain(__('reports.export.status'));
    expect($headers)->toContain(__('reports.export.priority'));
    expect($headers)->toContain(__('reports.export.assigned_tech'));
    expect($headers)->toContain(__('reports.export.requester'));
    expect($headers)->toContain(__('reports.export.created_at'));
});

// ─── Filters honored ──────────────────────────────────────────────────────────

it('queued export respects status filter', function () {
    Storage::fake('local');
    Notification::fake();
    app()->setLocale('en');

    $user = makeNonManagerWithReports();
    Ticket::factory()->create(['status' => 'in_progress']);
    Ticket::factory()->create(['status' => 'resolved']);

    $export = TicketExport::create([
        'user_id' => $user->id,
        'format' => 'csv',
        'filters' => ['status' => 'in_progress'],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $content = Storage::disk('local')->get($export->file_path);
    $lines = array_values(array_filter(explode("\n", ltrim($content, "\xEF\xBB\xBF"))));

    // header + 1 matching ticket
    expect(count($lines))->toBe(2);
});

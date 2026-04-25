<?php

use App\Modules\Reporting\Jobs\ExportTicketsJob;
use App\Modules\Reporting\Livewire\ReportPage;
use App\Modules\Reporting\Models\TicketExport;
use App\Modules\Reporting\Notifications\ExportReadyNotification;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeReportManager(): User
{
    $user = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $perm = Permission::firstOrCreate(
        ['key' => 'system.view-reports'],
        ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'group_key' => 'system']
    );
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);

    return $user;
}

// ─── Job dispatch via Livewire ─────────────────────────────────────────────────

it('dispatches ExportTicketsJob when queueExport is called via Livewire', function () {
    Queue::fake();

    $manager = makeReportManager();

    Livewire::actingAs($manager)
        ->test(ReportPage::class)
        ->call('queueExport', 'csv');

    Queue::assertPushed(ExportTicketsJob::class);
});

it('creates a pending TicketExport record on queue dispatch', function () {
    Queue::fake();

    $manager = makeReportManager();

    Livewire::actingAs($manager)
        ->test(ReportPage::class)
        ->call('queueExport', 'xlsx');

    expect(TicketExport::where('user_id', $manager->id)->where('format', 'xlsx')->exists())->toBeTrue();
    expect(TicketExport::where('user_id', $manager->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('sets exportQueued flag after queuing', function () {
    Queue::fake();

    $manager = makeReportManager();

    Livewire::actingAs($manager)
        ->test(ReportPage::class)
        ->call('queueExport', 'csv')
        ->assertSet('exportQueued', true);
});

// ─── Job execution ─────────────────────────────────────────────────────────────

it('job writes CSV file to non-public local storage', function () {
    Storage::fake('local');
    Notification::fake();

    $manager = makeReportManager();
    Ticket::factory()->create();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);

    $export->refresh();
    expect($export->status)->toBe('ready');
    expect($export->file_path)->toStartWith('exports/');
    Storage::disk('local')->assertExists($export->file_path);
});

it('job writes XLSX file to local storage', function () {
    Storage::fake('local');
    Notification::fake();

    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'xlsx',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);

    $export->refresh();
    expect($export->status)->toBe('ready');
    Storage::disk('local')->assertExists($export->file_path);
});

it('job sends database notification to requesting user when complete', function () {
    Storage::fake('local');
    Notification::fake();

    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);

    Notification::assertSentTo($manager, ExportReadyNotification::class);
});

it('job stores file outside public disk', function () {
    Storage::fake('local');
    Storage::fake('public');
    Notification::fake();

    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);

    $export->refresh();
    // Must be on local disk, not on public
    Storage::disk('local')->assertExists($export->file_path);
    Storage::disk('public')->assertMissing($export->file_path);
});

it('job marks status as failed on exception', function () {
    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    $job = new ExportTicketsJob($export->id);
    $job->failed(new RuntimeException('Disk full'));

    $export->refresh();
    expect($export->status)->toBe('failed');
});

// ─── Download route ────────────────────────────────────────────────────────────

it('download returns 404 when file has been deleted', function () {
    Storage::fake('local');

    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'file_path' => 'exports/'.$manager->id.'.csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'ready',
        'expires_at' => now()->addDay(),
    ]);

    // File NOT written — simulates already-deleted scenario
    $response = $this->actingAs($manager)
        ->get(route('reports.exports.download', $export->id));

    $response->assertNotFound();
});

it('download returns 403 for a different user', function () {
    Storage::fake('local');

    $manager = makeReportManager();
    $other = User::factory()->create(['is_super_user' => true]);

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'file_path' => 'exports/test.csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'ready',
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($other)
        ->get(route('reports.exports.download', $export->id))
        ->assertForbidden();
});

it('download streams ready file to the owner', function () {
    Storage::fake('local');
    Notification::fake();

    $manager = makeReportManager();

    $export = TicketExport::create([
        'user_id' => $manager->id,
        'format' => 'csv',
        'filters' => [],
        'locale' => 'en',
        'include_csat' => false,
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    ExportTicketsJob::dispatchSync($export->id);
    $export->refresh();

    $response = $this->actingAs($manager)
        ->get(route('reports.exports.download', $export->id));

    $response->assertOk();
});

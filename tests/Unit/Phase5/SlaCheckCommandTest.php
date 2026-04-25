<?php

use App\Modules\SLA\Events\SlaBreach;
use App\Modules\SLA\Events\SlaWarning;
use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('increments elapsed by business minutes when clock is running during working hours', function () {
    // Tuesday 10:00 is well inside default working hours (08:00–16:00, Sun–Thu)
    $t0 = Carbon::parse('2026-04-21 10:00');
    Carbon::setTestNow($t0);

    SlaPolicy::factory()->medium()->create();
    $ticket = Ticket::factory()->create(['priority' => 'medium']);

    $sla = TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'last_clock_start' => $t0->copy()->subMinutes(10), // 09:50
        'response_elapsed_minutes' => 0,
        'resolution_elapsed_minutes' => 0,
        'response_target_minutes' => 240,
        'resolution_target_minutes' => 1440,
        'is_clock_running' => true,
        'response_met_at' => null,
    ]);

    $this->artisan('sla:check')->assertExitCode(0);

    $sla->refresh();
    expect($sla->response_elapsed_minutes)->toBeGreaterThanOrEqual(10);
    expect($sla->resolution_elapsed_minutes)->toBeGreaterThanOrEqual(10);
});

it('does not increment elapsed during off-hours for non-24x7 policy', function () {
    // Saturday is not in the default working days (Sun–Thu)
    $t0 = Carbon::parse('2026-04-25 12:00'); // Saturday noon
    Carbon::setTestNow($t0);

    SlaPolicy::factory()->medium()->create();
    $ticket = Ticket::factory()->create(['priority' => 'medium']);

    $sla = TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'last_clock_start' => $t0->copy()->subMinutes(10), // Saturday 11:50
        'response_elapsed_minutes' => 5,
        'resolution_elapsed_minutes' => 5,
        'is_clock_running' => true,
    ]);

    $this->artisan('sla:check')->assertExitCode(0);

    $sla->refresh();
    expect($sla->response_elapsed_minutes)->toBe(5);
    expect($sla->resolution_elapsed_minutes)->toBe(5);
});

it('runs without exception when there are no running ticket_slas', function () {
    $this->artisan('sla:check')->assertExitCode(0);
});

it('is registered in the scheduler to run every minute', function () {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());
    $found = $events->first(fn ($e) => str_contains($e->command ?? '', 'sla:check'));

    expect($found)->not->toBeNull();
    expect($found->expression)->toBe('* * * * *');
});

it('fires SlaWarning event when command causes elapsed to cross warning threshold', function () {
    $t0 = Carbon::parse('2026-04-21 10:00'); // Tuesday — inside working hours
    Carbon::setTestNow($t0);

    SlaPolicy::factory()->medium()->create();
    $ticket = Ticket::factory()->create(['priority' => 'medium']);

    // 68% on_track → +10 working mins → 78% ≥ 75 threshold → SlaWarning
    $sla = TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'last_clock_start' => $t0->copy()->subMinutes(10),
        'response_elapsed_minutes' => 68,
        'resolution_elapsed_minutes' => 68,
        'response_target_minutes' => 100,
        'resolution_target_minutes' => 100,
        'response_status' => 'on_track',
        'resolution_status' => 'on_track',
        'is_clock_running' => true,
        'response_met_at' => null,
    ]);

    Event::fake([SlaWarning::class, SlaBreach::class]);

    $this->artisan('sla:check')->assertExitCode(0);

    Event::assertDispatched(SlaWarning::class, fn ($e) => $e->ticketId === $sla->ticket_id);
    Event::assertNotDispatched(SlaBreach::class);
});

it('fires SlaBreach event when command causes elapsed to exceed 100% of target', function () {
    $t0 = Carbon::parse('2026-04-21 10:00');
    Carbon::setTestNow($t0);

    SlaPolicy::factory()->medium()->create();
    $ticket = Ticket::factory()->create(['priority' => 'medium']);

    // 92% warning → +10 working mins → 102% ≥ 100% → SlaBreach
    $sla = TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'last_clock_start' => $t0->copy()->subMinutes(10),
        'response_elapsed_minutes' => 92,
        'resolution_elapsed_minutes' => 92,
        'response_target_minutes' => 100,
        'resolution_target_minutes' => 100,
        'response_status' => 'warning',
        'resolution_status' => 'warning',
        'is_clock_running' => true,
        'response_met_at' => null,
    ]);

    Event::fake([SlaWarning::class, SlaBreach::class]);

    $this->artisan('sla:check')->assertExitCode(0);

    Event::assertDispatched(SlaBreach::class, fn ($e) => $e->ticketId === $sla->ticket_id);
});

it('respects sla_warning_threshold from app_settings instead of default 75', function () {
    $t0 = Carbon::parse('2026-04-21 10:00');
    Carbon::setTestNow($t0);

    // app_settings is seeded by migration; just update the existing key
    DB::table('app_settings')
        ->where('key', 'sla_warning_threshold')
        ->update(['value' => '90']);

    SlaPolicy::factory()->medium()->create();
    $ticket = Ticket::factory()->create(['priority' => 'medium']);

    // 73% on_track → +6 working mins → 79%
    // Default threshold (75): 79 ≥ 75 → SlaWarning would fire
    // Custom threshold (90): 79 < 90 → SlaWarning must NOT fire
    $sla = TicketSla::factory()->create([
        'ticket_id' => $ticket->id,
        'last_clock_start' => $t0->copy()->subMinutes(6),
        'response_elapsed_minutes' => 73,
        'resolution_elapsed_minutes' => 73,
        'response_target_minutes' => 100,
        'resolution_target_minutes' => 100,
        'response_status' => 'on_track',
        'resolution_status' => 'on_track',
        'is_clock_running' => true,
        'response_met_at' => null,
    ]);

    Event::fake([SlaWarning::class, SlaBreach::class]);

    $this->artisan('sla:check')->assertExitCode(0);

    Event::assertNotDispatched(SlaWarning::class);
    Event::assertNotDispatched(SlaBreach::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

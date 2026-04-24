<?php

use App\Modules\SLA\Models\SlaPolicy;
use App\Modules\SLA\Models\TicketSla;
use App\Modules\Tickets\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;

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

afterEach(function () {
    Carbon::setTestNow();
});

<?php

use App\Modules\SLA\Services\BusinessHoursCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Default config: Sun–Thu 08:00–16:00 (480 min/day)

// ─── 24/7 mode ────────────────────────────────────────────────────────────────

it('returns wall-clock minutes when use_24x7 is true', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 22:00'); // Monday
    $to   = Carbon::parse('2026-04-21 02:00'); // Tuesday (4 hours later)

    expect($calc->minutesBetween($from, $to, use24x7: true))->toBe(240);
});

it('24x7 crosses weekend without skipping days', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-24 18:00'); // Friday
    $to   = Carbon::parse('2026-04-26 10:00'); // Sunday (40 hours)

    expect($calc->minutesBetween($from, $to, use24x7: true))->toBe(2400);
});

// ─── Entirely inside business hours ──────────────────────────────────────────

it('counts minutes entirely within one business day', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 09:00'); // Sunday 09:00
    $to   = Carbon::parse('2026-04-20 11:00'); // Sunday 11:00

    expect($calc->minutesBetween($from, $to))->toBe(120);
});

it('returns 0 when from equals to', function () {
    $calc = new BusinessHoursCalculator();
    $ts   = Carbon::parse('2026-04-20 10:00');

    expect($calc->minutesBetween($ts, $ts))->toBe(0);
});

it('returns 0 when from is after to', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 11:00');
    $to   = Carbon::parse('2026-04-20 09:00');

    expect($calc->minutesBetween($from, $to))->toBe(0);
});

// ─── Spanning end-of-business-day boundary ────────────────────────────────────

it('excludes off-hours after business day end', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 15:00'); // Sunday 15:00
    $to   = Carbon::parse('2026-04-20 18:00'); // Sunday 18:00 (past 16:00)

    // Only 15:00–16:00 counts = 60 minutes
    expect($calc->minutesBetween($from, $to))->toBe(60);
});

it('excludes off-hours before business day start', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 06:00'); // Sunday 06:00 (before 08:00)
    $to   = Carbon::parse('2026-04-20 10:00'); // Sunday 10:00

    // Only 08:00–10:00 counts = 120 minutes
    expect($calc->minutesBetween($from, $to))->toBe(120);
});

it('span from before-open to after-close on same day yields full business day', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 00:00'); // midnight
    $to   = Carbon::parse('2026-04-20 23:59'); // end of day

    // 08:00–16:00 = 480 minutes
    expect($calc->minutesBetween($from, $to))->toBe(480);
});

// ─── Cross-midnight ───────────────────────────────────────────────────────────

it('cross-midnight span excludes overnight off-hours', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 15:00'); // Sunday 15:00
    $to   = Carbon::parse('2026-04-21 10:00'); // Monday 10:00

    // Day 1 (Sun): 15:00–16:00 = 60 min
    // Day 2 (Mon): 08:00–10:00 = 120 min
    expect($calc->minutesBetween($from, $to))->toBe(180);
});

// ─── Multi-day spanning full business days ────────────────────────────────────

it('two consecutive business days yields 2x480 minutes', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-20 08:00'); // Sunday
    $to   = Carbon::parse('2026-04-21 16:00'); // Monday (2 full days)

    expect($calc->minutesBetween($from, $to))->toBe(960);
});

// ─── Non-working day skipping ─────────────────────────────────────────────────

it('skips a Saturday entirely (not in Sun–Thu working days)', function () {
    $calc = new BusinessHoursCalculator();
    // Saturday 2026-04-25
    $from = Carbon::parse('2026-04-25 08:00');
    $to   = Carbon::parse('2026-04-25 16:00');

    expect($calc->minutesBetween($from, $to))->toBe(0);
});

it('skips a Friday entirely (not in Sun–Thu working days)', function () {
    $calc = new BusinessHoursCalculator();
    // Friday 2026-04-24
    $from = Carbon::parse('2026-04-24 08:00');
    $to   = Carbon::parse('2026-04-24 16:00');

    expect($calc->minutesBetween($from, $to))->toBe(0);
});

it('span crossing a weekend skips Fri and Sat', function () {
    $calc = new BusinessHoursCalculator();
    // Thu 16:00 → Sun 08:00: Fri and Sat are non-working
    $from = Carbon::parse('2026-04-23 16:00'); // Thursday end of day
    $to   = Carbon::parse('2026-04-26 08:00'); // Sunday start of day

    // Thu: 16:00–16:00 = 0 (start at close)
    // Fri: skip
    // Sat: skip
    // Sun: 08:00–08:00 = 0 (end at open)
    expect($calc->minutesBetween($from, $to))->toBe(0);
});

it('span from Thursday afternoon to Monday morning skips weekend', function () {
    $calc = new BusinessHoursCalculator();
    $from = Carbon::parse('2026-04-23 14:00'); // Thursday 14:00
    $to   = Carbon::parse('2026-04-27 10:00'); // Monday 10:00

    // Thu: 14:00–16:00 = 120 min
    // Fri, Sat: skip
    // Sun: 08:00–16:00 = 480 min
    // Mon: 08:00–10:00 = 120 min
    expect($calc->minutesBetween($from, $to))->toBe(720);
});

// ─── app_settings override ───────────────────────────────────────────────────

it('respects working_days override from app_settings', function () {
    // app_settings is seeded by migration; update the relevant keys
    DB::table('app_settings')->where('key', 'working_days')->update(['value' => '["mon","tue","wed","thu","fri"]']);
    DB::table('app_settings')->where('key', 'business_hours_start')->update(['value' => '09:00']);
    DB::table('app_settings')->where('key', 'business_hours_end')->update(['value' => '17:00']);

    $calc = new BusinessHoursCalculator();
    // Friday 2026-04-24 (fri is now a working day)
    $from = Carbon::parse('2026-04-24 09:00');
    $to   = Carbon::parse('2026-04-24 17:00');

    expect($calc->minutesBetween($from, $to))->toBe(480);

    // Sunday 2026-04-26 (sun is no longer a working day)
    $from2 = Carbon::parse('2026-04-26 09:00');
    $to2   = Carbon::parse('2026-04-26 17:00');

    expect($calc->minutesBetween($from2, $to2))->toBe(0);
});

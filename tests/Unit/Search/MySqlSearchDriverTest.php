<?php

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Search\MySqlSearchDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeDriver(): MySqlSearchDriver
{
    return new MySqlSearchDriver();
}

function techUser(): User
{
    return User::factory()->create(['is_tech' => true]);
}

// ─── Query with results ───────────────────────────────────────────────────────

it('returns ticket matching the search term in subject', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $match = Ticket::factory()->create(['subject' => 'Printer is broken']);
    Ticket::factory()->create(['subject' => 'Network issue']);

    $results = makeDriver()->search('Printer');

    expect($results->items())->toHaveCount(1)
        ->and($results->items()[0]->id)->toBe($match->id);
});

it('returns ticket matching the search term in description', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $match = Ticket::factory()->create([
        'subject'     => 'Some issue',
        'description' => 'The keyboard is not responding',
    ]);
    Ticket::factory()->create([
        'subject'     => 'Other issue',
        'description' => 'Monitor problem',
    ]);

    $results = makeDriver()->search('keyboard');

    expect($results->items())->toHaveCount(1)
        ->and($results->items()[0]->id)->toBe($match->id);
});

it('returns ticket when search term matches a comment body', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $match = Ticket::factory()->create(['subject' => 'Hardware request']);
    Ticket::factory()->create(['subject' => 'Software request']);

    \Illuminate\Support\Facades\DB::table('comments')->insert([
        'id'          => \Illuminate\Support\Str::ulid(),
        'ticket_id'   => $match->id,
        'user_id'     => $tech->id,
        'body'        => 'The RAM module needs replacement',
        'is_internal' => false,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $results = makeDriver()->search('RAM module');

    expect(collect($results->items())->pluck('id'))->toContain($match->id);
});

// ─── Empty query ──────────────────────────────────────────────────────────────

it('returns all tickets when query is empty', function () {
    $tech = techUser();
    $this->actingAs($tech);

    Ticket::factory()->count(3)->create();

    $results = makeDriver()->search('');

    expect($results->total())->toBe(3);
});

it('does not throw on empty query string', function () {
    $tech = techUser();
    $this->actingAs($tech);

    expect(fn () => makeDriver()->search(''))->not->toThrow(\Throwable::class);
});

// ─── Unknown filter key ───────────────────────────────────────────────────────

it('ignores unknown filter keys without throwing', function () {
    $tech = techUser();
    $this->actingAs($tech);

    Ticket::factory()->count(2)->create();

    expect(fn () => makeDriver()->search('', ['bogus_key' => 'value', 'another_invalid' => 123]))
        ->not->toThrow(\Throwable::class);
});

it('still returns results when unknown filter keys are mixed with valid ones', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $ticket = Ticket::factory()->create(['priority' => 'high']);
    Ticket::factory()->create(['priority' => 'low']);

    $results = makeDriver()->search('', ['priority' => 'high', 'unknown' => 'ignored']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($ticket->id);
});

// ─── Filters ─────────────────────────────────────────────────────────────────

it('filters by status', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $open = Ticket::factory()->create(['status' => 'awaiting_assignment']);
    Ticket::factory()->create(['status' => 'resolved']);

    $results = makeDriver()->search('', ['status' => 'awaiting_assignment']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($open->id);
});

it('filters by priority', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $critical = Ticket::factory()->create(['priority' => 'critical']);
    Ticket::factory()->create(['priority' => 'low']);

    $results = makeDriver()->search('', ['priority' => 'critical']);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($critical->id);
});

it('filters by requester_id', function () {
    $tech      = techUser();
    $requester = User::factory()->create();
    $other     = User::factory()->create();
    $this->actingAs($tech);

    $mine  = Ticket::factory()->create(['requester_id' => $requester->id]);
    $theirs = Ticket::factory()->create(['requester_id' => $other->id]);

    $results = makeDriver()->search('', ['requester_id' => $requester->id]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($mine->id);
});

it('filters by date_from', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $old   = Ticket::factory()->create(['created_at' => now()->subDays(10)]);
    $recent = Ticket::factory()->create(['created_at' => now()->subDay()]);

    $results = makeDriver()->search('', ['date_from' => now()->subDays(3)->toDateString()]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($recent->id);
});

it('filters by date_to', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $old   = Ticket::factory()->create(['created_at' => now()->subDays(10)]);
    $recent = Ticket::factory()->create(['created_at' => now()->subDay()]);

    $results = makeDriver()->search('', ['date_to' => now()->subDays(5)->toDateString()]);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->id)->toBe($old->id);
});

// ─── Sort ─────────────────────────────────────────────────────────────────────

it('sorts by created_at desc by default', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $first  = Ticket::factory()->create(['created_at' => now()->subHours(2)]);
    $second = Ticket::factory()->create(['created_at' => now()->subHour()]);

    $results = makeDriver()->search('');

    expect($results->items()[0]->id)->toBe($second->id);
});

it('sorts by created_at asc when direction is asc', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $first  = Ticket::factory()->create(['created_at' => now()->subHours(2)]);
    $second = Ticket::factory()->create(['created_at' => now()->subHour()]);

    $results = makeDriver()->search('', [], 'created_at', 'asc');

    expect($results->items()[0]->id)->toBe($first->id);
});

it('falls back to created_at sort for unknown sort column', function () {
    $tech = techUser();
    $this->actingAs($tech);

    Ticket::factory()->count(2)->create();

    expect(fn () => makeDriver()->search('', [], 'bogus_column', 'desc'))
        ->not->toThrow(\Throwable::class);
});

// ─── Pagination ───────────────────────────────────────────────────────────────

it('returns a LengthAwarePaginator', function () {
    $tech = techUser();
    $this->actingAs($tech);

    $results = makeDriver()->search('');

    expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
});

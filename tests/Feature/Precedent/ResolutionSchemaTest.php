<?php

use App\Modules\Precedent\Models\Resolution;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

// ── Table structure ───────────────────────────────────────────────────────────

it('resolutions table exists with expected columns', function () {
    expect(Schema::hasTable('resolutions'))->toBeTrue();

    foreach ([
        'id', 'ticket_id', 'summary', 'root_cause', 'steps_taken',
        'parts_resources', 'time_spent_minutes', 'resolution_type',
        'linked_resolution_id', 'link_notes', 'usage_count',
        'created_by', 'created_at', 'updated_at',
    ] as $col) {
        expect(Schema::hasColumn('resolutions', $col))->toBeTrue("Column {$col} missing");
    }
});

// ── Factory: all enum values ──────────────────────────────────────────────────

it('factory creates a resolution with known_fix type', function () {
    $r = Resolution::factory()->knownFix()->create();
    expect($r->resolution_type)->toBe('known_fix');
});

it('factory creates a resolution with workaround type', function () {
    $r = Resolution::factory()->workaround()->create();
    expect($r->resolution_type)->toBe('workaround');
});

it('factory creates a resolution with escalated_externally type', function () {
    $r = Resolution::factory()->escalatedExternally()->create();
    expect($r->resolution_type)->toBe('escalated_externally');
});

it('factory creates a resolution with other type', function () {
    $r = Resolution::factory()->other()->create();
    expect($r->resolution_type)->toBe('other');
});

it('factory produces a valid ULID primary key', function () {
    $r = Resolution::factory()->create();
    expect($r->id)->toHaveLength(26);
});

it('usage_count defaults to zero', function () {
    $r = Resolution::factory()->create();
    expect($r->fresh()->usage_count)->toBe(0);
});

// ── Relationships ─────────────────────────────────────────────────────────────

it('ticket() relation resolves to the correct Ticket', function () {
    $ticket = Ticket::factory()->resolved()->create();
    $r = Resolution::factory()->create(['ticket_id' => $ticket->id]);

    expect($r->ticket->id)->toBe($ticket->id);
});

it('creator() relation resolves to the correct User', function () {
    $user = User::factory()->create();
    $r = Resolution::factory()->create(['created_by' => $user->id]);

    expect($r->creator->id)->toBe($user->id);
});

it('linkedResolution() returns null when not linked', function () {
    $r = Resolution::factory()->create(['linked_resolution_id' => null]);
    expect($r->linkedResolution)->toBeNull();
});

it('linkedResolution() resolves the target when set', function () {
    $target = Resolution::factory()->create();
    $child = Resolution::factory()->create(['linked_resolution_id' => $target->id]);

    expect($child->linkedResolution->id)->toBe($target->id);
});

it('linkedBy() returns all resolutions that point to this one', function () {
    $target = Resolution::factory()->create();
    Resolution::factory()->count(3)->create(['linked_resolution_id' => $target->id]);

    expect($target->linkedBy)->toHaveCount(3);
});

it('linkedBy() returns empty collection when nothing links to this resolution', function () {
    $r = Resolution::factory()->create();
    expect($r->linkedBy)->toHaveCount(0);
});

// ── Constraints ───────────────────────────────────────────────────────────────

it('rejects duplicate ticket_id (UNIQUE constraint)', function () {
    $ticket = Ticket::factory()->resolved()->create();
    Resolution::factory()->create(['ticket_id' => $ticket->id]);

    Resolution::factory()->create(['ticket_id' => $ticket->id]);
})->throws(QueryException::class);

it('sets linked_resolution_id to NULL when referenced resolution is deleted', function () {
    $target = Resolution::factory()->create();
    $child = Resolution::factory()->create(['linked_resolution_id' => $target->id]);

    $target->delete();

    expect($child->fresh()->linked_resolution_id)->toBeNull();
});

it('deletes resolution when its ticket is deleted (ON DELETE CASCADE)', function () {
    $ticket = Ticket::factory()->resolved()->create();
    $r = Resolution::factory()->create(['ticket_id' => $ticket->id]);
    $rid = $r->id;

    $ticket->forceDelete();

    expect(Resolution::find($rid))->toBeNull();
});

it('rejects resolution when summary is missing', function () {
    $ticket = Ticket::factory()->resolved()->create();
    $user = User::factory()->create();

    Resolution::create([
        'ticket_id'       => $ticket->id,
        'steps_taken'     => '<p>steps</p>',
        'resolution_type' => 'known_fix',
        'created_by'      => $user->id,
    ]);
})->throws(QueryException::class);

<?php

use App\Modules\Admin\Models\Tag;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;

// ─── scopeActive() ────────────────────────────────────────────────────────────

it('active scope returns only active tags', function () {
    Tag::factory()->create(['is_active' => true]);
    Tag::factory()->inactive()->create();

    expect(Tag::active()->count())->toBe(1);
});

it('active scope excludes soft-deleted tags', function () {
    $active  = Tag::factory()->create(['is_active' => true]);
    $deleted = Tag::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(Tag::active()->count())->toBe(1)
        ->and(Tag::active()->first()->id)->toBe($active->id);
});

// ─── Pivot attachment ─────────────────────────────────────────────────────────

it('tag can be attached to a ticket via the pivot table', function () {
    $tag    = Tag::factory()->create();
    $ticket = Ticket::factory()->create();

    DB::table('ticket_tag')->insert([
        'ticket_id' => $ticket->id,
        'tag_id'    => $tag->id,
    ]);

    expect(DB::table('ticket_tag')
        ->where('ticket_id', $ticket->id)
        ->where('tag_id', $tag->id)
        ->count()
    )->toBe(1);
});

it('ticket_tag pivot enforces uniqueness', function () {
    $tag    = Tag::factory()->create();
    $ticket = Ticket::factory()->create();

    DB::table('ticket_tag')->insert(['ticket_id' => $ticket->id, 'tag_id' => $tag->id]);

    expect(fn () => DB::table('ticket_tag')->insert(['ticket_id' => $ticket->id, 'tag_id' => $tag->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// ─── localizedName() ─────────────────────────────────────────────────────────

it('localizedName returns name_ar when locale is ar', function () {
    app()->setLocale('ar');
    $tag = Tag::factory()->create(['name_ar' => 'عاجل', 'name_en' => 'Urgent']);

    expect($tag->localizedName())->toBe('عاجل');
});

it('localizedName returns name_en when locale is en', function () {
    app()->setLocale('en');
    $tag = Tag::factory()->create(['name_ar' => 'عاجل', 'name_en' => 'Urgent']);

    expect($tag->localizedName())->toBe('Urgent');
});

// ─── Casts ────────────────────────────────────────────────────────────────────

it('is_active casts to boolean', function () {
    $tag = Tag::factory()->create(['is_active' => true]);

    expect($tag->is_active)->toBeTrue()->toBeBool();
});

// ─── Factory ──────────────────────────────────────────────────────────────────

it('tag factory produces a valid row', function () {
    $tag = Tag::factory()->create();

    expect($tag->id)->toHaveLength(26)
        ->and($tag->name_ar)->not->toBeEmpty()
        ->and($tag->name_en)->not->toBeEmpty()
        ->and($tag->is_active)->toBeTrue();
});

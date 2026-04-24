<?php

use App\Modules\CSAT\Livewire\CsatPromptModal;
use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders visible for a requester with a pending rating', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->create([
        'ticket_id' => $ticket->id,
        'requester_id' => $requester->id,
        'tech_id' => $tech->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->assertSet('visible', true)
        ->assertSeeText($tech->full_name);
});

it('is not visible when no pending ratings exist', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->assertSet('visible', false);
});

it('dismiss increments dismissed_count and hides modal', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    $rating = CsatRating::factory()->create([
        'ticket_id' => $ticket->id,
        'requester_id' => $requester->id,
        'tech_id' => $tech->id,
        'dismissed_count' => 0,
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->call('dismiss')
        ->assertSet('visible', false);

    expect($rating->fresh()->dismissed_count)->toBe(1);
    expect($rating->fresh()->status)->toBe('pending');
});

it('submit saves rating and hides modal', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    $rating = CsatRating::factory()->create([
        'ticket_id' => $ticket->id,
        'requester_id' => $requester->id,
        'tech_id' => $tech->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->set('rating', 4)
        ->set('comment', 'Great service')
        ->call('submit')
        ->assertSet('visible', false)
        ->assertHasNoErrors();

    $fresh = $rating->fresh();
    expect($fresh->rating)->toBe(4)
        ->and($fresh->comment)->toBe('Great service')
        ->and($fresh->status)->toBe('submitted')
        ->and($fresh->submitted_at)->not->toBeNull();
});

it('submit requires a rating between 1 and 5', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->create([
        'ticket_id' => $ticket->id,
        'requester_id' => $requester->id,
        'tech_id' => $tech->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->set('rating', 0)
        ->call('submit')
        ->assertHasErrors(['rating']);
});

it('is not visible for a tech user', function () {
    $tech = User::factory()->create(['is_tech' => true]);

    Livewire::actingAs($tech)
        ->test(CsatPromptModal::class)
        ->assertSet('visible', false);
});

it('is not visible for a super user', function () {
    $manager = User::factory()->create(['is_super_user' => true, 'is_tech' => true]);

    Livewire::actingAs($manager)
        ->test(CsatPromptModal::class)
        ->assertSet('visible', false);
});

it('is not visible for an expired rating', function () {
    $requester = User::factory()->create(['is_tech' => false, 'is_super_user' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->expired()->create([
        'ticket_id' => $ticket->id,
        'requester_id' => $requester->id,
        'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($requester)
        ->test(CsatPromptModal::class)
        ->assertSet('visible', false);
});

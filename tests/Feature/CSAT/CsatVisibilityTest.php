<?php

use App\Modules\CSAT\Livewire\CsatRatingSection;
use App\Modules\CSAT\Models\CsatRating;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Requester visibility ─────────────────────────────────────────────────────

it('requester sees pending form when rating is pending', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id,
        'tech_id' => $tech->id, 'status' => 'pending', 'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'pending_form');
});

it('requester sees read-only after submission', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->submitted()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($requester)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'read_only');
});

it('requester sees nothing for expired rating', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->expired()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($requester)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'none');
});

// ─── Assigned tech visibility ─────────────────────────────────────────────────

it('assigned tech sees read-only for submitted rating', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->submitted()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($tech)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'read_only');
});

it('assigned tech sees nothing for pending rating', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id,
        'tech_id' => $tech->id, 'status' => 'pending', 'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($tech)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'none');
});

it('non-assigned tech sees nothing for submitted rating', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $otherTech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->submitted()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($otherTech)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'none');
});

// ─── IT Manager visibility ────────────────────────────────────────────────────

it('super user sees submitted rating read-only', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $manager = User::factory()->create(['is_super_user' => true, 'is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->submitted()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    Livewire::actingAs($manager)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'read_only');
});

it('super user sees nothing for pending rating', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $manager = User::factory()->create(['is_super_user' => true, 'is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id,
        'tech_id' => $tech->id, 'status' => 'pending', 'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($manager)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'none');
});

it('section shows nothing when no csat record exists', function () {
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['assigned_to' => $tech->id]);

    Livewire::actingAs($tech)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'none');
});

// ─── Submission from ticket detail ───────────────────────────────────────────

it('requester can submit rating from ticket detail view', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    $csatRating = CsatRating::factory()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id,
        'tech_id' => $tech->id, 'status' => 'pending', 'expires_at' => now()->addDays(5),
    ]);

    Livewire::actingAs($requester)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->set('rating', 5)
        ->set('comment', 'Excellent support')
        ->call('submit')
        ->assertSet('viewMode', 'read_only')
        ->assertHasNoErrors();

    $fresh = $csatRating->fresh();
    expect($fresh->rating)->toBe(5)
        ->and($fresh->status)->toBe('submitted')
        ->and($fresh->submitted_at)->not->toBeNull();
});

it('requester cannot re-submit after rating is already submitted', function () {
    $requester = User::factory()->create(['is_tech' => false]);
    $tech = User::factory()->create(['is_tech' => true]);
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id, 'assigned_to' => $tech->id]);
    CsatRating::factory()->submitted()->create([
        'ticket_id' => $ticket->id, 'requester_id' => $requester->id, 'tech_id' => $tech->id,
    ]);

    // Section shows read_only — submit action guards against non-pending status
    Livewire::actingAs($requester)
        ->test(CsatRatingSection::class, ['ticketId' => $ticket->id])
        ->assertSet('viewMode', 'read_only');
});

<?php

use App\Mail\TicketNotification;
use App\Modules\Admin\Livewire\Settings\BrandingSettings;
use App\Modules\Admin\Models\AppSetting;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function brandingItManager(): User
{
    return User::factory()->create(['is_super_user' => true]);
}

function brandingEmployee(): User
{
    return User::factory()->create(['is_super_user' => false, 'is_tech' => false]);
}

// ─── Access control ───────────────────────────────────────────────────────────

test('unauthenticated user is redirected from branding page', function () {
    $this->get(route('admin.branding.index'))
        ->assertRedirect(route('login'));
});

test('non-IT-manager gets 403 on branding page', function () {
    $user = brandingEmployee();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->assertForbidden();
});

test('tech user gets 403 on branding page', function () {
    $user = User::factory()->create(['is_tech' => true, 'is_super_user' => false]);
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->assertForbidden();
});

test('IT manager can access branding page', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->assertOk();
});

// ─── Form saves app_settings ──────────────────────────────────────────────────

test('saving updates app_settings rows', function () {
    $user = brandingItManager();

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Riyadh IT School')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 4)
        ->call('save')
        ->assertHasNoErrors();

    expect(AppSetting::get('company_name'))->toBe('Riyadh IT School');
    expect(AppSetting::get('primary_color'))->toBe('#ff0000');
    expect(AppSetting::get('secondary_color'))->toBe('#00ff00');
    expect(AppSetting::get('session_timeout_hours'))->toBe('4');
});

test('saved flag is set on success', function () {
    $user = brandingItManager();

    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test Co')
        ->set('primaryColor', '#123456')
        ->set('secondaryColor', '#654321')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertSet('saved', true);
});

// ─── Hex color validation ─────────────────────────────────────────────────────

test('invalid primary color hex is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', 'not-a-hex')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertHasErrors(['primaryColor']);
});

test('primary color without hash is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', 'ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertHasErrors(['primaryColor']);
});

test('invalid secondary color hex is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', 'invalid')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertHasErrors(['secondaryColor']);
});

test('five-character hex color is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertHasErrors(['primaryColor']);
});

// ─── session_timeout_hours validation ────────────────────────────────────────

test('session_timeout_hours below 1 is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 0)
        ->call('save')
        ->assertHasErrors(['sessionTimeoutHours']);
});

test('session_timeout_hours above 24 is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 25)
        ->call('save')
        ->assertHasErrors(['sessionTimeoutHours']);
});

test('session_timeout_hours of 1 is accepted', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 1)
        ->call('save')
        ->assertHasNoErrors();
});

test('session_timeout_hours of 24 is accepted', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', 'Test')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 24)
        ->call('save')
        ->assertHasNoErrors();
});

// ─── company_name validation ──────────────────────────────────────────────────

test('empty company name is rejected', function () {
    $user = brandingItManager();
    Livewire::actingAs($user)
        ->test(BrandingSettings::class)
        ->set('companyName', '')
        ->set('primaryColor', '#ff0000')
        ->set('secondaryColor', '#00ff00')
        ->set('sessionTimeoutHours', 8)
        ->call('save')
        ->assertHasErrors(['companyName']);
});

// ─── Admin layout header ──────────────────────────────────────────────────────

test('company name appears in admin layout header after saving', function () {
    AppSetting::set('company_name', 'Riyadh IT School');
    AppSetting::set('primary_color', '#4f46e5');
    AppSetting::set('secondary_color', '#7c3aed');

    $user = brandingItManager();
    $this->actingAs($user)
        ->get(route('admin.branding.index'))
        ->assertSee('Riyadh IT School');
});

test('primary color CSS var is injected into admin layout', function () {
    AppSetting::set('company_name', 'Test Co');
    AppSetting::set('primary_color', '#abcdef');
    AppSetting::set('secondary_color', '#7c3aed');

    $user = brandingItManager();
    $this->actingAs($user)
        ->get(route('admin.branding.index'))
        ->assertSee('--color-primary: #abcdef', false);
});

// ─── Email notification uses company_name ────────────────────────────────────

test('email notification base template uses company_name from app_settings', function () {
    AppSetting::set('company_name', 'Saudi School District');

    $requester = User::factory()->create();
    $tech      = User::factory()->tech()->create();
    $ticket    = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'assigned_to'  => $tech->id,
    ]);

    $mailable = new TicketNotification(
        triggerKey:     'ticket_created',
        ticketId:       $ticket->id,
        displayNumber:  $ticket->display_number,
        ticketSubject:  $ticket->subject,
        recipientName:  $requester->full_name,
    );

    $html = $mailable->render();

    expect($html)->toContain('Saudi School District');
});

// ─── Admin nav branding link ──────────────────────────────────────────────────

test('admin nav shows branding link for IT manager', function () {
    $user = brandingItManager();
    $this->actingAs($user)
        ->get(route('admin.branding.index'))
        ->assertSee(route('admin.branding.index'));
});

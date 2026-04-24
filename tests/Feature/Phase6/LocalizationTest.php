<?php

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Livewire\EmployeeDashboard;
use App\Modules\Tickets\Livewire\ManagerDashboard;
use App\Modules\Tickets\Livewire\TechDashboard;
use Livewire\Livewire;

// ── Helpers ───────────────────────────────────────────────────────────────────

function localeEmployee(string $locale): User
{
    return User::factory()->create(['is_tech' => false, 'locale' => $locale]);
}

function localeTech(string $locale): User
{
    return User::factory()->create(['is_tech' => true, 'locale' => $locale]);
}

function localeManager(string $locale): User
{
    return User::factory()->create(['is_super_user' => true, 'locale' => $locale]);
}

function applyAppLocale(string $locale): void
{
    app()->setLocale($locale);
    view()->share('dir', $locale === 'ar' ? 'rtl' : 'ltr');
    view()->share('lang', $locale);
}

// ── Employee Dashboard ────────────────────────────────────────────────────────

it('employee dashboard renders in EN without Blade errors', function () {
    applyAppLocale('en');
    $employee = localeEmployee('en');

    Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->assertOk();
});

it('employee dashboard renders in AR without Blade errors', function () {
    applyAppLocale('ar');
    $employee = localeEmployee('ar');

    Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->assertOk();
});

it('employee dashboard AR render contains AR title', function () {
    applyAppLocale('ar');
    $employee = localeEmployee('ar');

    $html = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->html();

    expect($html)->toContain('لوحة التحكم الخاصة بي');
});

it('employee dashboard AR render does not contain EN title', function () {
    applyAppLocale('ar');
    $employee = localeEmployee('ar');

    $html = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->html();

    expect($html)->not->toContain('My Dashboard');
});

it('employee dashboard EN render contains EN title', function () {
    applyAppLocale('en');
    $employee = localeEmployee('en');

    $html = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->html();

    expect($html)->toContain('My Dashboard');
});

it('employee dashboard AR render contains AR sort labels', function () {
    applyAppLocale('ar');
    $employee = localeEmployee('ar');

    $html = Livewire::actingAs($employee)
        ->test(EmployeeDashboard::class)
        ->html();

    expect($html)->toContain('ترتيب بحسب');
});

// ── Tech Dashboard ────────────────────────────────────────────────────────────

it('tech dashboard renders in EN without Blade errors', function () {
    applyAppLocale('en');
    $tech = localeTech('en');

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->assertOk();
});

it('tech dashboard renders in AR without Blade errors', function () {
    applyAppLocale('ar');
    $tech = localeTech('ar');

    Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->assertOk();
});

it('tech dashboard AR render contains AR title', function () {
    applyAppLocale('ar');
    $tech = localeTech('ar');

    $html = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->html();

    expect($html)->toContain('لوحة تحكم الفني');
});

it('tech dashboard AR render does not contain EN title', function () {
    applyAppLocale('ar');
    $tech = localeTech('ar');

    $html = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->html();

    expect($html)->not->toContain('Tech Dashboard');
});

it('tech dashboard priority checkboxes use translation keys not raw English', function () {
    applyAppLocale('ar');
    $tech = localeTech('ar');

    $html = Livewire::actingAs($tech)
        ->test(TechDashboard::class)
        ->html();

    // AR priority labels should appear
    expect($html)->toContain('حرجة')
        ->and($html)->toContain('عالية');

    // Raw English capitalised strings should NOT appear as UI labels
    expect($html)->not->toContain('>Critical<')
        ->and($html)->not->toContain('>High<');
});

// ── Manager Dashboard ─────────────────────────────────────────────────────────

it('manager dashboard renders in EN without Blade errors', function () {
    applyAppLocale('en');
    $manager = localeManager('en');

    Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->assertOk();
});

it('manager dashboard renders in AR without Blade errors', function () {
    applyAppLocale('ar');
    $manager = localeManager('ar');

    Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->assertOk();
});

it('manager dashboard AR render contains AR title', function () {
    applyAppLocale('ar');
    $manager = localeManager('ar');

    $html = Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->html();

    expect($html)->toContain('لوحة تحكم مدير تقنية المعلومات');
});

it('manager dashboard AR render does not contain EN title', function () {
    applyAppLocale('ar');
    $manager = localeManager('ar');

    $html = Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->html();

    expect($html)->not->toContain('IT Manager Dashboard');
});

it('manager dashboard AR render contains AR sort labels', function () {
    applyAppLocale('ar');
    $manager = localeManager('ar');

    $html = Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->html();

    expect($html)->toContain('ترتيب بحسب');
});

it('manager dashboard EN render contains EN sort labels', function () {
    applyAppLocale('en');
    $manager = localeManager('en');

    $html = Livewire::actingAs($manager)
        ->test(ManagerDashboard::class)
        ->html();

    expect($html)->toContain('Sort By');
});

<?php

use App\Modules\Admin\Livewire\ResponseTemplates\ResponseTemplateIndex;
use App\Modules\Communication\Models\ResponseTemplate;
use App\Modules\Shared\Models\Permission;
use App\Modules\Shared\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function templateManageUser(): User
{
    $user = User::factory()->create();
    $perm = Permission::where('key', 'system.manage-response-templates')->firstOrFail();
    $user->permissions()->attach($perm->id, ['granted_by' => $user->id, 'granted_at' => now()]);
    return $user;
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

// ─── Route access ─────────────────────────────────────────────────────────────

test('unauthenticated user is redirected from admin response-templates', function () {
    $this->get(route('admin.response-templates.index'))
        ->assertRedirect(route('login'));
});

test('user without system.manage-response-templates cannot access admin response-templates', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.response-templates.index'))
        ->assertForbidden();
});

test('user with system.manage-response-templates can access admin response-templates', function () {
    $user = templateManageUser();
    $this->actingAs($user)
        ->get(route('admin.response-templates.index'))
        ->assertOk();
});

test('super user can access admin response-templates', function () {
    $user = User::factory()->superUser()->create();
    $this->actingAs($user)
        ->get(route('admin.response-templates.index'))
        ->assertOk();
});

// ─── Create ──────────────────────────────────────────────────────────────────

test('authorised user can create a response template', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب ترحيب')
        ->set('formTitleEn', 'Welcome Template')
        ->set('formBodyAr', '<p>مرحباً</p>')
        ->set('formBodyEn', '<p>Hello</p>')
        ->set('formIsInternal', false)
        ->set('formIsActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $template = ResponseTemplate::first();
    expect($template)->not->toBeNull();
    expect($template->title_en)->toBe('Welcome Template');
    expect($template->title_ar)->toBe('قالب ترحيب');
    expect($template->is_internal)->toBeFalse();
    expect($template->is_active)->toBeTrue();
});

test('creating a template without title_ar fails validation', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', '')
        ->set('formTitleEn', 'Welcome')
        ->set('formBodyAr', '<p>مرحباً</p>')
        ->set('formBodyEn', '<p>Hello</p>')
        ->call('save')
        ->assertHasErrors(['formTitleAr' => 'required']);
});

test('creating a template without title_en fails validation', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', '')
        ->set('formBodyAr', '<p>مرحباً</p>')
        ->set('formBodyEn', '<p>Hello</p>')
        ->call('save')
        ->assertHasErrors(['formTitleEn' => 'required']);
});

test('creating a template without body_ar fails validation', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', 'Template')
        ->set('formBodyAr', '')
        ->set('formBodyEn', '<p>Hello</p>')
        ->call('save')
        ->assertHasErrors(['formBodyAr' => 'required']);
});

test('creating a template without body_en fails validation', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', 'Template')
        ->set('formBodyAr', '<p>مرحباً</p>')
        ->set('formBodyEn', '')
        ->call('save')
        ->assertHasErrors(['formBodyEn' => 'required']);
});

// ─── XSS sanitization ─────────────────────────────────────────────────────────

test('XSS script tag in body is stripped on save', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', 'XSS Template')
        ->set('formBodyAr', '<p>Hello</p><script>alert("xss")</script>')
        ->set('formBodyEn', '<p>Hi</p><script>alert("xss")</script>')
        ->call('save');

    $template = ResponseTemplate::first();
    expect($template->body_ar)->not->toContain('<script>');
    expect($template->body_en)->not->toContain('<script>');
    expect($template->body_ar)->toContain('<p>Hello</p>');
});

test('onclick attribute in body is stripped on save', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', 'onclick Template')
        ->set('formBodyAr', '<p onclick="alert(1)">Test</p>')
        ->set('formBodyEn', '<p onclick="alert(1)">Test</p>')
        ->call('save');

    $template = ResponseTemplate::first();
    expect($template->body_en)->not->toContain('onclick');
});

test('javascript href in body is stripped on save', function () {
    $user = templateManageUser();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openCreate')
        ->set('formTitleAr', 'قالب')
        ->set('formTitleEn', 'JS href Template')
        ->set('formBodyAr', '<a href="javascript:void(0)">click</a>')
        ->set('formBodyEn', '<a href="javascript:void(0)">click</a>')
        ->call('save');

    $template = ResponseTemplate::first();
    expect($template->body_en)->not->toContain('javascript:');
});

// ─── Edit ────────────────────────────────────────────────────────────────────

test('authorised user can edit a response template', function () {
    $user     = templateManageUser();
    $template = ResponseTemplate::factory()->create([
        'title_en' => 'Old Title',
        'body_en'  => '<p>Old body</p>',
    ]);

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('openEdit', $template->id)
        ->set('formTitleEn', 'New Title')
        ->set('formBodyEn', '<p>New body</p>')
        ->call('save')
        ->assertHasNoErrors();

    expect($template->fresh()->title_en)->toBe('New Title');
    expect($template->fresh()->body_en)->toContain('New body');
});

// ─── Toggle active ────────────────────────────────────────────────────────────

test('authorised user can deactivate a response template', function () {
    $user     = templateManageUser();
    $template = ResponseTemplate::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('toggleActive', $template->id);

    expect($template->fresh()->is_active)->toBeFalse();
});

test('authorised user can reactivate a response template', function () {
    $user     = templateManageUser();
    $template = ResponseTemplate::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('toggleActive', $template->id);

    expect($template->fresh()->is_active)->toBeTrue();
});

// ─── Soft-delete ──────────────────────────────────────────────────────────────

test('authorised user can soft-delete a response template', function () {
    $user     = templateManageUser();
    $template = ResponseTemplate::factory()->create();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->call('delete', $template->id);

    expect(ResponseTemplate::withTrashed()->find($template->id)->deleted_at)->not->toBeNull();
    expect(ResponseTemplate::find($template->id))->toBeNull();
});

test('soft-deleted template is shown in list (withTrashed)', function () {
    $user     = templateManageUser();
    $template = ResponseTemplate::factory()->create(['title_en' => 'SoftDeletedTemplate']);
    $template->delete();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->assertSee('SoftDeletedTemplate');
});

// ─── Filter by is_internal ────────────────────────────────────────────────────

test('filter by internal shows only internal templates', function () {
    $user     = templateManageUser();
    $internal = ResponseTemplate::factory()->create(['title_en' => 'InternalTpl', 'is_internal' => true]);
    $public   = ResponseTemplate::factory()->create(['title_en' => 'PublicTpl',   'is_internal' => false]);

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->set('filterInternal', '1')
        ->assertSee('InternalTpl')
        ->assertDontSee('PublicTpl');
});

test('filter by public shows only public templates', function () {
    $user     = templateManageUser();
    $internal = ResponseTemplate::factory()->create(['title_en' => 'InternalTpl', 'is_internal' => true]);
    $public   = ResponseTemplate::factory()->create(['title_en' => 'PublicTpl',   'is_internal' => false]);

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->set('filterInternal', '0')
        ->assertDontSee('InternalTpl')
        ->assertSee('PublicTpl');
});

// ─── Permission gates ─────────────────────────────────────────────────────────

test('user without system.manage-response-templates cannot mount ResponseTemplateIndex', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ResponseTemplateIndex::class)
        ->assertForbidden();
});

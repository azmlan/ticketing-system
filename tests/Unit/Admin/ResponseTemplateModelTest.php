<?php

use App\Modules\Communication\Models\ResponseTemplate;

// ─── scopeActive() ────────────────────────────────────────────────────────────

it('active scope returns only active response templates', function () {
    ResponseTemplate::factory()->create(['is_active' => true]);
    ResponseTemplate::factory()->inactive()->create();

    expect(ResponseTemplate::active()->count())->toBe(1);
});

it('active scope excludes soft-deleted response templates', function () {
    $active  = ResponseTemplate::factory()->create(['is_active' => true]);
    $deleted = ResponseTemplate::factory()->create(['is_active' => true]);
    $deleted->delete();

    expect(ResponseTemplate::active()->count())->toBe(1)
        ->and(ResponseTemplate::active()->first()->id)->toBe($active->id);
});

// ─── Casts ────────────────────────────────────────────────────────────────────

it('is_internal casts to boolean', function () {
    $template = ResponseTemplate::factory()->create(['is_internal' => true]);

    expect($template->is_internal)->toBeTrue()->toBeBool();
});

it('is_internal defaults to true', function () {
    $template = ResponseTemplate::factory()->create();

    expect($template->is_internal)->toBeTrue();
});

it('public template has is_internal false', function () {
    $template = ResponseTemplate::factory()->public()->create();

    expect($template->is_internal)->toBeFalse();
});

it('is_active casts to boolean', function () {
    $template = ResponseTemplate::factory()->create(['is_active' => true]);

    expect($template->is_active)->toBeTrue()->toBeBool();
});

// ─── localizedName() ─────────────────────────────────────────────────────────

it('localizedName returns title_ar when locale is ar', function () {
    app()->setLocale('ar');
    $template = ResponseTemplate::factory()->create([
        'title_ar' => 'رد ترحيبي',
        'title_en' => 'Welcome Reply',
    ]);

    expect($template->localizedName())->toBe('رد ترحيبي');
});

it('localizedName returns title_en when locale is en', function () {
    app()->setLocale('en');
    $template = ResponseTemplate::factory()->create([
        'title_ar' => 'رد ترحيبي',
        'title_en' => 'Welcome Reply',
    ]);

    expect($template->localizedName())->toBe('Welcome Reply');
});

// ─── Factory ──────────────────────────────────────────────────────────────────

it('response template factory produces a valid row', function () {
    $template = ResponseTemplate::factory()->create();

    expect($template->id)->toHaveLength(26)
        ->and($template->title_ar)->not->toBeEmpty()
        ->and($template->title_en)->not->toBeEmpty()
        ->and($template->body_ar)->not->toBeEmpty()
        ->and($template->body_en)->not->toBeEmpty()
        ->and($template->is_active)->toBeTrue();
});

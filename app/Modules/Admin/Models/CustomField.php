<?php

namespace App\Modules\Admin\Models;

use Database\Factories\CustomFieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomField extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'field_type',
        'is_required',
        'scope_type',
        'scope_category_id',
        'display_order',
        'is_active',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'is_required'   => 'boolean',
            'is_active'     => 'boolean',
            'display_order' => 'integer',
            'version'       => 'integer',
        ];
    }

    protected static function newFactory(): CustomFieldFactory
    {
        return CustomFieldFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}

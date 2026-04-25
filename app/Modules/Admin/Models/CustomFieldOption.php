<?php

namespace App\Modules\Admin\Models;

use Database\Factories\CustomFieldOptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomFieldOption extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'custom_field_id',
        'value_ar',
        'value_en',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    protected static function newFactory(): CustomFieldOptionFactory
    {
        return CustomFieldOptionFactory::new();
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localizedValue(): string
    {
        return app()->getLocale() === 'ar' ? $this->value_ar : $this->value_en;
    }
}

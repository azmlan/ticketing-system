<?php

namespace App\Modules\Communication\Models;

use Database\Factories\ResponseTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResponseTemplate extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected static function newFactory(): ResponseTemplateFactory
    {
        return ResponseTemplateFactory::new();
    }

    protected $fillable = [
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'is_internal',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }
}

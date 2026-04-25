<?php

namespace App\Modules\Admin\Models;

use Database\Factories\CustomFieldValueFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ticket_id',
        'custom_field_id',
        'value',
    ];

    protected static function newFactory(): CustomFieldValueFactory
    {
        return CustomFieldValueFactory::new();
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }
}

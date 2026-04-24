<?php

namespace App\Modules\SLA\Models;

use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'sla_policies';

    protected static function newFactory(): SlaPolicyFactory
    {
        return SlaPolicyFactory::new();
    }

    protected $fillable = [
        'priority',
        'response_target_minutes',
        'resolution_target_minutes',
        'use_24x7',
    ];

    protected function casts(): array
    {
        return [
            'use_24x7' => 'boolean',
            'response_target_minutes' => 'integer',
            'resolution_target_minutes' => 'integer',
        ];
    }
}

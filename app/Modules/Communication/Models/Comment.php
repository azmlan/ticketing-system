<?php

namespace App\Modules\Communication\Models;

use App\Modules\Communication\Models\Scopes\InternalCommentScope;
use App\Modules\Shared\Models\User;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory, HasUlids;

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InternalCommentScope);
    }

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

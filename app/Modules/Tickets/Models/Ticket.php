<?php

namespace App\Modules\Tickets\Models;

// Admin module is a shared-data module; cross-module import is permitted here per CLAUDE.md note.
use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\CustomFieldValue;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Admin\Models\Tag;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Enums\TicketPriority;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Scopes\EmployeeTicketScope;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'display_number',
        'subject',
        'description',
        'status',
        'priority',
        'category_id',
        'subcategory_id',
        'group_id',
        'assigned_to',
        'requester_id',
        'location_id',
        'department_id',
        'close_reason',
        'close_reason_text',
        'incident_origin',
        'resolved_at',
        'closed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new EmployeeTicketScope);
    }

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(TransferRequest::class);
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'ticket_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag');
    }
}

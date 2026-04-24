<?php

namespace Database\Factories;

use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    public function definition(): array
    {
        $ticketId = Ticket::withoutGlobalScopes()->inRandomOrder()->value('id')
            ?? Ticket::factory()->create()->id;

        return [
            'ticket_id'     => $ticketId,
            'original_name' => fake()->word() . '.jpg',
            'file_path'     => 'tickets/' . $ticketId . '/' . strtolower((string) \Illuminate\Support\Str::ulid()),
            'file_size'     => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'mime_type'     => 'image/jpeg',
            'uploaded_by'   => User::factory(),
        ];
    }
}

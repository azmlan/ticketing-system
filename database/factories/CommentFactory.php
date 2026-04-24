<?php

namespace Database\Factories;

use App\Modules\Communication\Models\Comment;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'ticket_id'   => Ticket::factory(),
            'user_id'     => User::factory(),
            'body'        => '<p>' . fake()->paragraph() . '</p>',
            'is_internal' => true,
        ];
    }

    public function public(): static
    {
        return $this->state(['is_internal' => false]);
    }

    public function internal(): static
    {
        return $this->state(['is_internal' => true]);
    }
}

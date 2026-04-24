<?php

namespace App\Modules\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $ticketId,
        public readonly string $commentId,
        public readonly bool $isInternal,
    ) {}
}

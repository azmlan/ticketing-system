<?php

namespace App\Modules\Assignment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $ticketId,
        public readonly string $toUserId,
        public readonly string $displayNumber,
        public readonly string $ticketSubject,
    ) {}
}

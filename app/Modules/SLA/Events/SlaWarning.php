<?php

namespace App\Modules\SLA\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaWarning
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $ticketId,
        public readonly string $displayNumber,
        public readonly string $ticketSubject,
        public readonly ?string $assignedTechId,
        public readonly string $type,
    ) {}
}

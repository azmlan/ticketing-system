<?php

namespace App\Modules\Admin\Events;

use App\Modules\Shared\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserPromotedToTech
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly User $promotedBy,
    ) {}
}

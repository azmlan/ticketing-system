<?php

namespace App\Modules\Communication\Listeners;

use App\Modules\Communication\Events\CommentCreated;

class HandleCommentCreated
{
    public function handle(CommentCreated $event): void
    {
        // Phase 4: comment notifications are out of scope.
        // Stub exists to satisfy the event-bus contract; Phase 5+ will fill this in.
    }
}

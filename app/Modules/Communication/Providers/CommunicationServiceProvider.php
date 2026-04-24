<?php

namespace App\Modules\Communication\Providers;

use App\Modules\Assignment\Events\TransferRequestCreated;
use App\Modules\Communication\Events\CommentCreated;
use App\Modules\Communication\Listeners\HandleCommentCreated;
use App\Modules\Communication\Listeners\HandleTicketStatusChanged;
use App\Modules\Communication\Listeners\HandleTransferRequestCreated;
use App\Modules\Communication\Livewire\AddComment;
use App\Modules\Tickets\Events\TicketStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CommunicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('communication.add-comment', AddComment::class);

        Event::listen(TicketStatusChanged::class, HandleTicketStatusChanged::class);
        Event::listen(TransferRequestCreated::class, HandleTransferRequestCreated::class);
        Event::listen(CommentCreated::class, HandleCommentCreated::class);
    }
}

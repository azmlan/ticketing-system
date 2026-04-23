<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Shared\Providers\PermissionServiceProvider::class,
    App\Modules\Tickets\Providers\TicketsServiceProvider::class,
    App\Modules\Assignment\Providers\AssignmentServiceProvider::class,
    App\Modules\Escalation\Providers\EscalationServiceProvider::class,
];

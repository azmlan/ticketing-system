<?php

/*
|--------------------------------------------------------------------------
| Role Bundles (SPEC.md §6.4)
|--------------------------------------------------------------------------
| Convenience sets for bulk permission assignment. NOT database entities.
| Used by: app:create-superuser command (Task 1.7), promotion flow (Phase 8).
*/

return [

    'technician' => [
        'ticket.view-all',
        'user.view-directory',
    ],

    'group_manager' => [
        'ticket.view-all',
        'user.view-directory',
        'group.manage-members',
    ],

    'it_manager' => array_keys(require __DIR__.'/permissions.php'),

];

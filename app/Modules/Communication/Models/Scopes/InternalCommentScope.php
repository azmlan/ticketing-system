<?php

namespace App\Modules\Communication\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InternalCommentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // CLI/queue contexts have no authenticated user — let queries through unfiltered.
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        // SuperUsers, techs, and anyone with ticket.view-all see internal comments.
        if ($user->is_super_user || $user->is_tech || $user->hasPermission('ticket.view-all')) {
            return;
        }

        $builder->where('is_internal', false);
    }
}

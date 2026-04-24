<?php

namespace App\Modules\Shared\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchServiceInterface
{
    public function search(
        string $query,
        array $filters = [],
        string $sort = 'created_at',
        string $direction = 'desc'
    ): LengthAwarePaginator;
}

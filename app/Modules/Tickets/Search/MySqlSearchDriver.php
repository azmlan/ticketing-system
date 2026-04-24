<?php

namespace App\Modules\Tickets\Search;

use App\Modules\Shared\Contracts\SearchServiceInterface;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MySqlSearchDriver implements SearchServiceInterface
{
    private const ALLOWED_FILTERS = [
        'status', 'requester_id', 'assigned_to',
        'category_id', 'group_id', 'priority',
        'date_from', 'date_to',
    ];

    private const ALLOWED_SORTS = ['created_at', 'updated_at', 'priority'];

    public function search(
        string $query,
        array $filters = [],
        string $sort = 'created_at',
        string $direction = 'desc'
    ): LengthAwarePaginator {
        $builder = Ticket::query();

        $this->applyTextSearch($builder, $query);
        $this->applyFilters($builder, $filters);
        $this->applySort($builder, $sort, $direction);

        return $builder->paginate(config('ticketing.dashboard.per_page', 25));
    }

    private function applyTextSearch($builder, string $query): void
    {
        if ($query === '') {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $builder->where(function ($q) use ($query) {
                $q->whereRaw('MATCH(subject, description) AGAINST(? IN BOOLEAN MODE)', [$query])
                  ->orWhereIn('id', function ($sub) use ($query) {
                      $sub->select('ticket_id')
                          ->from('comments')
                          ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$query]);
                  });
            });
        } else {
            $term = '%' . $query . '%';
            $builder->where(function ($q) use ($term) {
                $q->where('subject', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term)
                  ->orWhereIn('id', function ($sub) use ($term) {
                      $sub->select('ticket_id')
                          ->from('comments')
                          ->where('body', 'LIKE', $term);
                  });
            });
        }
    }

    private function applyFilters($builder, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (! in_array($key, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            match ($key) {
                'date_from' => $builder->whereDate('created_at', '>=', $value),
                'date_to'   => $builder->whereDate('created_at', '<=', $value),
                default     => $builder->where($key, $value),
            };
        }
    }

    private function applySort($builder, string $sort, string $direction): void
    {
        $col = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'created_at';
        $dir = in_array(strtolower($direction), ['asc', 'desc'], true) ? $direction : 'desc';

        if ($col === 'priority') {
            $builder->orderByRaw(
                "CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END {$dir}"
            );
        } else {
            $builder->orderBy($col, $dir);
        }
    }
}

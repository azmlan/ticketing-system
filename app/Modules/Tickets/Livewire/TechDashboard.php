<?php

namespace App\Modules\Tickets\Livewire;

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Admin\Models\Subcategory;
use App\Modules\Assignment\Services\TransferService;
use App\Modules\Shared\Contracts\SearchServiceInterface;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TransferRequest;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TechDashboard extends Component
{
    use WithPagination;

    // ── Filter bar ────────────────────────────────────────────────────────────
    public array $filterStatus = [];

    public array $filterPriority = [];

    public string $filterCategory = '';

    public string $filterSubcategory = '';

    public array $filterGroups = [];   // persisted to users.preferences

    public string $filterAssignedTo = '';  // '' = any, 'unassigned' = NULL, or a user ULID

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    // Sort — empty string means "use panel-default" (SLA for my-tickets, priority for queue)
    public string $sortBy = '';

    public string $sortDir = 'desc';

    private const OPEN_STATUSES = [
        'awaiting_assignment',
        'in_progress',
        'on_hold',
        'awaiting_approval',
        'action_required',
        'awaiting_final_approval',
    ];

    private const ALLOWED_SORTS = ['created_at', 'priority', 'updated_at'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->is_tech || $user->is_super_user || $user->hasPermission('ticket.view-assigned'),
            403
        );

        // Restore saved group selection from preferences
        $saved = data_get($user->preferences, 'tech_dashboard.groups', []);
        $this->filterGroups = is_array($saved) ? $saved : [];
    }

    public function updatedFilterGroups(): void
    {
        $user = auth()->user();
        $prefs = $user->preferences ?? [];
        data_set($prefs, 'tech_dashboard.groups', $this->filterGroups);
        $user->update(['preferences' => $prefs]);
        $this->resetPage('queuePage');
        $this->resetPage('myPage');
    }

    public function updatedFilterCategory(): void
    {
        $this->filterSubcategory = '';
        $this->resetPage('queuePage');
        $this->resetPage('myPage');
    }

    public function updated(string $property): void
    {
        $filterProps = ['search', 'sortBy', 'sortDir', 'dateFrom', 'dateTo', 'filterAssignedTo', 'filterSubcategory'];
        $arrayPrefixes = ['filterStatus', 'filterPriority'];

        $isFilter = in_array($property, $filterProps, true)
            || array_reduce($arrayPrefixes, fn ($carry, $prefix) => $carry || str_starts_with($property, $prefix), false);

        if ($isFilter) {
            $this->resetPage('queuePage');
            $this->resetPage('myPage');
        }
    }

    public function acceptTransfer(string $transferId): void
    {
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->accept($tr, auth()->user());
    }

    public function declineTransfer(string $transferId): void
    {
        $tr = TransferRequest::findOrFail($transferId);
        app(TransferService::class)->reject($tr, auth()->user());
    }

    public function render()
    {
        $user = auth()->user();
        $groupIds = DB::table('group_user')->where('user_id', $user->id)->pluck('group_id')->all();

        $searchIds = $this->resolveSearchIds();

        $perPage = config('ticketing.dashboard.per_page', 25);
        $queueTickets = $this->buildQueueQuery($groupIds, $searchIds)->with(['category'])->paginate($perPage, pageName: 'queuePage');
        $myTickets = $this->buildMyTicketsQuery($user->id, $searchIds)->with(['category'])->paginate($perPage, pageName: 'myPage');

        $allIds = $queueTickets->pluck('id')->merge($myTickets->pluck('id'))->unique()->all();
        $slaMap = DB::table('ticket_sla')
            ->whereIn('ticket_id', $allIds)
            ->get()
            ->keyBy('ticket_id');

        $pendingTransfers = TransferRequest::where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['ticket', 'fromUser'])
            ->get();

        $stats = $this->computeStats($user->id);

        // Dropdown data
        $categories = Category::where('is_active', true)->orderBy('name_en')->get();
        $subcategories = $this->filterCategory !== ''
            ? Subcategory::where('category_id', $this->filterCategory)->where('is_active', true)->orderBy('name_en')->get()
            : collect();
        $techUsers = User::where('is_tech', true)->orderBy('full_name')->get();
        $groups = Group::where('is_active', true)->orderBy('name_en')->get();

        return view('livewire.tickets.tech-dashboard', compact(
            'queueTickets', 'myTickets', 'slaMap', 'pendingTransfers', 'stats',
            'categories', 'subcategories', 'techUsers', 'groups'
        ));
    }

    // ── Query builders ────────────────────────────────────────────────────────

    private function buildQueueQuery(array $techGroupIds, ?array $searchIds)
    {
        // Narrow queue to the intersection of tech's groups and any group filter
        $queueGroupIds = $this->resolveQueueGroupIds($techGroupIds);

        if (empty($queueGroupIds)) {
            return Ticket::query()->whereRaw('0=1');
        }

        $query = Ticket::query()
            ->whereIn('group_id', $queueGroupIds)
            ->whereNull('assigned_to');

        $this->applyStatusFilter($query);
        $this->applyCommonFilters($query);

        if ($searchIds !== null) {
            $query->whereIn('tickets.id', $searchIds);
        }

        $this->applySort($query, defaultPrioritySort: true);

        return $query;
    }

    private function buildMyTicketsQuery(string $userId, ?array $searchIds)
    {
        $query = Ticket::query()->where('assigned_to', $userId);

        $this->applyStatusFilter($query);
        $this->applyCommonFilters($query);

        if (! empty($this->filterGroups)) {
            $query->whereIn('group_id', $this->filterGroups);
        }

        if ($searchIds !== null) {
            $query->whereIn('tickets.id', $searchIds);
        }

        if ($this->sortBy === '') {
            // Default: SLA urgency → priority → date (task 6.3 behaviour)
            $query->leftJoin('ticket_sla', 'tickets.id', '=', 'ticket_sla.ticket_id')
                ->select('tickets.*')
                ->orderByRaw("CASE
                      WHEN ticket_sla.resolution_status = 'breached' OR ticket_sla.response_status = 'breached' THEN 0
                      WHEN ticket_sla.resolution_status = 'warning' OR ticket_sla.response_status = 'warning' THEN 1
                      WHEN ticket_sla.resolution_status IS NOT NULL THEN 2
                      ELSE 3
                  END")
                ->orderByRaw("CASE priority
                      WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3
                  END")
                ->orderBy('tickets.created_at');
        } else {
            $this->applySort($query, defaultPrioritySort: false);
        }

        return $query;
    }

    // ── Filter helpers ────────────────────────────────────────────────────────

    private function applyStatusFilter($query): void
    {
        if (! empty($this->filterStatus)) {
            $query->whereIn('status', $this->filterStatus);
        } else {
            $query->whereIn('status', self::OPEN_STATUSES);
        }
    }

    private function applyCommonFilters($query): void
    {
        if (! empty($this->filterPriority)) {
            $query->whereIn('priority', $this->filterPriority);
        }

        if ($this->filterCategory !== '') {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterSubcategory !== '') {
            $query->where('subcategory_id', $this->filterSubcategory);
        }

        if ($this->filterAssignedTo === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($this->filterAssignedTo !== '') {
            $query->where('assigned_to', $this->filterAssignedTo);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('tickets.created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('tickets.created_at', '<=', $this->dateTo);
        }
    }

    private function applySort($query, bool $defaultPrioritySort): void
    {
        $col = in_array($this->sortBy, self::ALLOWED_SORTS, true) ? $this->sortBy : 'created_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc'], true) ? $this->sortDir : 'desc';

        if ($col === 'priority') {
            $order = $dir === 'desc' ? 'ASC' : 'DESC'; // DESC priority = critical first = lower CASE value first
            $query->orderByRaw("CASE priority
                WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3
            END {$order}");
        } elseif ($col === 'created_at' && $defaultPrioritySort && $this->sortBy === '') {
            $query->orderByRaw("CASE priority
                WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3
            END")->orderBy('created_at');
        } else {
            $query->orderBy($col, $dir);
        }
    }

    private function resolveQueueGroupIds(array $techGroupIds): array
    {
        if (empty($this->filterGroups)) {
            return $techGroupIds;
        }

        return array_values(array_intersect($techGroupIds, $this->filterGroups));
    }

    private function resolveSearchIds(): ?array
    {
        if ($this->search === '') {
            return null;
        }

        $filters = [];
        if (! empty($this->filterStatus)) {
            $filters['status'] = $this->filterStatus[0]; // SearchServiceInterface takes scalar
        }
        if ($this->filterCategory !== '') {
            $filters['category_id'] = $this->filterCategory;
        }
        if ($this->dateFrom !== '') {
            $filters['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo !== '') {
            $filters['date_to'] = $this->dateTo;
        }

        $col = in_array($this->sortBy, self::ALLOWED_SORTS, true) ? $this->sortBy : 'created_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc']) ? $this->sortDir : 'desc';

        $results = app(SearchServiceInterface::class)->search($this->search, $filters, $col, $dir);

        return $results->pluck('id')->all();
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    private function computeStats(string $userId): array
    {
        $open = Ticket::query()
            ->where('assigned_to', $userId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->count();

        $resolvedWeek = Ticket::query()
            ->where('assigned_to', $userId)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfWeek())
            ->count();

        $resolvedMonth = Ticket::query()
            ->where('assigned_to', $userId)
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', now()->startOfMonth())
            ->count();

        $closedTotal = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->where('tickets.assigned_to', $userId)
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->count();

        $compliant = DB::table('ticket_sla')
            ->join('tickets', 'ticket_sla.ticket_id', '=', 'tickets.id')
            ->where('tickets.assigned_to', $userId)
            ->whereIn('tickets.status', ['resolved', 'closed'])
            ->where('ticket_sla.resolution_status', 'on_track')
            ->count();

        return [
            'open' => $open,
            'resolved_week' => $resolvedWeek,
            'resolved_month' => $resolvedMonth,
            'sla_compliance' => $closedTotal > 0 ? (int) round(($compliant / $closedTotal) * 100) : 0,
        ];
    }
}

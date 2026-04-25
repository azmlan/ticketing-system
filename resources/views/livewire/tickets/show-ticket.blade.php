<div>
@php
    $statusBadge = match($ticket->status->value) {
        'awaiting_assignment'    => ['pill' => 'bg-status-awaiting/10 text-status-awaiting',     'dot' => 'bg-status-awaiting'],
        'in_progress'            => ['pill' => 'bg-status-inprogress/10 text-status-inprogress', 'dot' => 'bg-status-inprogress'],
        'on_hold'                => ['pill' => 'bg-status-onhold/10 text-status-onhold',         'dot' => 'bg-status-onhold'],
        'awaiting_approval',
        'awaiting_final_approval'=> ['pill' => 'bg-status-approval/10 text-status-approval',     'dot' => 'bg-status-approval'],
        'action_required'        => ['pill' => 'bg-status-action/10 text-status-action',         'dot' => 'bg-status-action'],
        'resolved'               => ['pill' => 'bg-status-resolved/10 text-status-resolved',     'dot' => 'bg-status-resolved'],
        'closed'                 => ['pill' => 'bg-status-closed/10 text-status-closed',         'dot' => 'bg-status-closed'],
        'cancelled'              => ['pill' => 'bg-status-cancelled/10 text-status-cancelled',   'dot' => 'bg-status-cancelled'],
        default                  => ['pill' => 'bg-status-awaiting/10 text-status-awaiting',     'dot' => 'bg-status-awaiting'],
    };

    $priorityBadge = match($ticket->priority?->value) {
        'low'      => ['pill' => 'bg-priority-low/10 text-priority-low',           'dot' => 'bg-priority-low'],
        'high'     => ['pill' => 'bg-priority-high/10 text-priority-high',         'dot' => 'bg-priority-high'],
        'critical' => ['pill' => 'bg-priority-critical/10 text-priority-critical', 'dot' => 'bg-priority-critical'],
        default    => ['pill' => 'bg-priority-medium/10 text-priority-medium',     'dot' => 'bg-priority-medium'],
    };

    $isClosed = in_array($ticket->status->value, ['closed', 'cancelled']);
    $isAssignedToMe = $ticket->assigned_to === auth()->id();
@endphp

<div class="flex gap-6 items-start" x-data="{ tab: 'activity' }">

    {{-- ── Main column ──────────────────────────────────────────────── --}}
    <div class="flex-1 min-w-0 space-y-6">

        {{-- Header card --}}
        <div class="bg-surface border border-border rounded shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-text-muted font-medium mb-1 tracking-wide">{{ $ticket->display_number }}</p>
                    <h1 class="text-2xl font-semibold text-text-base leading-snug">{{ $ticket->subject }}</h1>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold flex-shrink-0 {{ $statusBadge['pill'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }} me-1.5"></span>
                    {{ __('tickets.status.' . $ticket->status->value) }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-text-secondary">
                <span>
                    <span class="text-text-muted">{{ __('tickets.show.requester') }}:</span>
                    <span class="ms-1 font-medium text-text-base">{{ $ticket->requester->full_name }}</span>
                </span>
                <span title="{{ $ticket->created_at->format('Y-m-d H:i') }}">
                    <span class="text-text-muted">{{ __('tickets.show.created_at') }}:</span>
                    <span class="ms-1">{{ $ticket->created_at->diffForHumans() }}</span>
                </span>
                @if ($ticket->category)
                    <span>
                        <span class="text-text-muted">{{ __('tickets.show.category') }}:</span>
                        <span class="ms-1">{{ $ticket->category->localizedName() }}</span>
                    </span>
                @endif
                @if ($ticket->subcategory)
                    <span>
                        <span class="text-text-muted">{{ __('tickets.show.subcategory') }}:</span>
                        <span class="ms-1">{{ $ticket->subcategory->localizedName() }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Description card --}}
        <div class="bg-surface border border-border rounded shadow-sm p-6">
            <h2 class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-4">{{ __('tickets.show.description') }}</h2>
            <div class="prose max-w-none text-sm text-text-base leading-relaxed">
                {!! $ticket->description !!}
            </div>
        </div>

        {{-- Tabs card --}}
        <div class="bg-surface border border-border rounded shadow-sm">

            {{-- Tab nav --}}
            <div class="flex border-b border-border">
                <button @click="tab = 'activity'"
                        :class="tab === 'activity' ? 'border-b-2 border-primary-500 text-primary-500' : 'text-text-muted hover:text-text-secondary'"
                        class="px-5 py-3.5 text-sm font-medium transition-colors -mb-px">
                    {{ __('tickets.show.tab_activity') }}
                </button>
                <button @click="tab = 'details'"
                        :class="tab === 'details' ? 'border-b-2 border-primary-500 text-primary-500' : 'text-text-muted hover:text-text-secondary'"
                        class="px-5 py-3.5 text-sm font-medium transition-colors -mb-px">
                    {{ __('tickets.show.tab_details') }}
                </button>
                <button @click="tab = 'files'"
                        :class="tab === 'files' ? 'border-b-2 border-primary-500 text-primary-500' : 'text-text-muted hover:text-text-secondary'"
                        class="px-5 py-3.5 text-sm font-medium transition-colors -mb-px">
                    {{ __('tickets.show.tab_files') }}
                    @if ($ticket->attachments->isNotEmpty())
                        <span class="ms-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-surface-alt text-xs font-semibold text-text-muted">
                            {{ $ticket->attachments->count() }}
                        </span>
                    @endif
                </button>
            </div>

            {{-- Activity tab --}}
            <div x-show="tab === 'activity'" class="p-6">
                @livewire('communication.add-comment', ['ticketUlid' => $ticket->id], key('comments-' . $ticket->id))
            </div>

            {{-- Details tab --}}
            <div x-show="tab === 'details'" x-cloak class="p-6 space-y-6">
                @auth
                    @livewire('precedent.auto-suggest-panel', ['ticket' => $ticket], key('auto-suggest-' . $ticket->id))
                @endauth

                @if ($customFieldEntries->isNotEmpty())
                    <div>
                        <h3 class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-3">{{ __('tickets.show.custom_fields') }}</h3>
                        <dl class="divide-y divide-border rounded border border-border">
                            @foreach ($customFieldEntries as $entry)
                                <div class="px-4 py-3 flex gap-4">
                                    <dt class="text-sm font-medium text-text-secondary w-48 shrink-0 {{ $entry['inactive'] ? 'opacity-60' : '' }}">
                                        {{ $entry['label'] }}
                                        @if ($entry['inactive'])
                                            <span class="ms-1 text-xs text-text-muted">({{ __('tickets.show.field_inactive') }})</span>
                                        @endif
                                    </dt>
                                    <dd class="text-sm text-text-base {{ $entry['inactive'] ? 'opacity-60' : '' }}">
                                        {{ $entry['value'] !== '' && $entry['value'] !== null ? $entry['value'] : '—' }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @else
                    <p class="text-sm text-text-muted">{{ __('tickets.show.no_custom_fields') }}</p>
                @endif
            </div>

            {{-- Files tab --}}
            <div x-show="tab === 'files'" x-cloak class="p-6">
                @if ($ticket->attachments->isNotEmpty())
                    <ul class="divide-y divide-border rounded border border-border">
                        @foreach ($ticket->attachments as $attachment)
                            <li class="flex items-center gap-3 px-4 py-3">
                                <svg class="w-4 h-4 text-text-muted flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                </svg>
                                <a href="{{ route('tickets.attachments.show', [$ticket->id, $attachment->id]) }}"
                                   class="text-sm text-primary-500 hover:text-primary-600 hover:underline flex-1 truncate">
                                    {{ $attachment->original_filename }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto w-12 h-12 text-text-muted mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        <p class="text-sm text-text-muted">{{ __('tickets.show.no_attachments') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Escalation sections (full-width, below tabs) --}}
        @auth
            @if (auth()->user()->is_tech && $ticket->status->value === 'in_progress')
                <div class="bg-surface border border-border rounded shadow-sm p-6">
                    @livewire('escalation.submit-condition-report', ['ticketId' => $ticket->id], key('condition-report-' . $ticket->id))
                </div>
            @endif
        @endauth

        @can('escalation.approve')
            @if ($ticket->status->value === 'awaiting_approval')
                <div class="bg-surface border border-border rounded shadow-sm p-6">
                    @livewire('escalation.review-condition-report', ['ticketId' => $ticket->id], key('review-cr-' . $ticket->id))
                </div>
            @endif
        @endcan

        @if ($ticket->status->value === 'action_required' && auth()->id() === $ticket->requester_id)
            <div class="bg-surface border border-border rounded shadow-sm p-6">
                @livewire('escalation.upload-signed-maintenance-request', ['ticketId' => $ticket->id], key('upload-signed-' . $ticket->id))
            </div>
        @endif

        @if ($ticket->status->value === 'action_required' && auth()->id() !== $ticket->requester_id)
            @auth
                @php
                    $canDownload = auth()->id() === $ticket->assigned_to
                        || auth()->user()->is_super_user
                        || auth()->user()->hasPermission('escalation.approve');
                @endphp
                @if ($canDownload)
                    <div class="bg-surface border border-border rounded shadow-sm p-6">
                        <h3 class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-4">{{ __('escalation.maintenance_request.title') }}</h3>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'ar']) }}"
                               class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-text-base bg-surface border border-border rounded hover:bg-surface-alt transition-colors">
                                {{ __('escalation.maintenance_request.export_ar') }}
                            </a>
                            <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'en']) }}"
                               class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-text-base bg-surface border border-border rounded hover:bg-surface-alt transition-colors">
                                {{ __('escalation.maintenance_request.export_en') }}
                            </a>
                        </div>
                    </div>
                @endif
            @endauth
        @endif

        @can('escalation.approve')
            @if ($ticket->status->value === 'awaiting_final_approval')
                <div class="bg-surface border border-border rounded shadow-sm p-6">
                    @livewire('escalation.review-signed-maintenance-request', ['ticketId' => $ticket->id], key('review-signed-' . $ticket->id))
                </div>
            @endif
        @endcan

        @auth
            @livewire('csat.rating-section', ['ticketId' => $ticket->id], key('csat-' . $ticket->id))
        @endauth

    </div>

    {{-- ── Right sidebar ─────────────────────────────────────────────── --}}
    <aside class="w-80 flex-shrink-0 space-y-4">

        {{-- Assignment card --}}
        <div class="bg-surface border border-border rounded shadow-sm">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-xs font-semibold text-text-muted uppercase tracking-widest">{{ __('tickets.show.section_assignment') }}</h2>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-text-muted">{{ __('tickets.show.assigned_to') }}</span>
                    <span class="text-sm font-medium text-text-base">
                        {{ $ticket->assignedTo?->full_name ?? __('tickets.show.unassigned') }}
                    </span>
                </div>

                @if ($ticket->group)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-text-muted">{{ __('tickets.show.group') }}</span>
                        <span class="text-sm text-text-base">{{ $ticket->group->localizedName() }}</span>
                    </div>
                @endif

                @can('selfAssign', $ticket)
                    <div class="pt-2 border-t border-border">
                        <button wire:click="selfAssign" type="button"
                                class="w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-500 rounded hover:bg-primary-600 transition-colors">
                            {{ __('tickets.show.actions.self_assign') }}
                        </button>
                    </div>
                @endcan

                @can('assign', $ticket)
                    <div class="pt-2 border-t border-border space-y-2">
                        <select wire:model="assignToUserId"
                                class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                            <option value="">{{ __('tickets.show.select_tech') }}</option>
                            @foreach ($techs as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                            @endforeach
                        </select>
                        <button wire:click="managerAssign" type="button"
                                class="w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-500 rounded hover:bg-primary-600 transition-colors">
                            {{ __('tickets.show.actions.assign_to') }}
                        </button>
                    </div>
                @endcan

                @can('requestTransfer', $ticket)
                    @if (! $pendingTransfer)
                        <div class="pt-2 border-t border-border space-y-2">
                            <select wire:model="transferToUserId"
                                    class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                <option value="">{{ __('tickets.show.select_tech') }}</option>
                                @foreach ($techs as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                                @endforeach
                            </select>
                            <button wire:click="requestTransfer" type="button"
                                    class="w-full px-4 py-2.5 text-sm font-medium text-text-base bg-surface border border-border rounded hover:bg-surface-alt transition-colors">
                                {{ __('tickets.show.actions.request_transfer') }}
                            </button>
                        </div>
                    @endif
                @endcan

                @if ($pendingTransfer && $pendingTransfer->to_user_id === auth()->id())
                    <div class="pt-2 border-t border-border space-y-2">
                        <p class="text-xs text-text-muted">{{ __('tickets.show.pending_transfer') }}</p>
                        <div class="flex gap-2">
                            <button wire:click="acceptTransfer('{{ $pendingTransfer->id }}')" type="button"
                                    class="flex-1 px-3 py-2 text-sm font-medium text-white bg-success rounded hover:opacity-90 transition-colors">
                                {{ __('tickets.show.actions.accept_transfer') }}
                            </button>
                            <button wire:click="rejectTransfer('{{ $pendingTransfer->id }}')" type="button"
                                    class="flex-1 px-3 py-2 text-sm font-medium text-white bg-danger rounded hover:opacity-90 transition-colors">
                                {{ __('tickets.show.actions.reject_transfer') }}
                            </button>
                        </div>
                    </div>
                @endif

                @if ($pendingTransfer && $pendingTransfer->from_user_id === auth()->id())
                    <div class="pt-2 border-t border-border">
                        <p class="text-xs text-text-muted mb-2">{{ __('tickets.show.transfer_pending') }}</p>
                        <button wire:click="revokeTransfer('{{ $pendingTransfer->id }}')" type="button"
                                class="w-full px-4 py-2.5 text-sm font-medium text-text-base bg-surface border border-border rounded hover:bg-surface-alt transition-colors">
                            {{ __('tickets.show.actions.revoke_transfer') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Classification card (Priority + SLA) --}}
        <div class="bg-surface border border-border rounded shadow-sm">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-xs font-semibold text-text-muted uppercase tracking-widest">{{ __('tickets.show.section_classification') }}</h2>
            </div>
            <div class="p-4 space-y-3">

                @if ($ticket->priority)
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-text-muted">{{ __('tickets.show.priority') }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold {{ $priorityBadge['pill'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $priorityBadge['dot'] }} me-1.5"></span>
                            {{ __('tickets.priority.' . $ticket->priority->value) }}
                        </span>
                    </div>
                @endif

                @if ($ticketSla)
                    <div class="{{ $ticket->priority ? 'pt-2 border-t border-border' : '' }}">
                        <p class="text-xs text-text-muted mb-2">{{ __('sla.indicator.title') }}</p>
                        <div class="space-y-1.5">
                            <x-sla-status-badge :status="$ticketSla->response_status" type="response" />
                            <x-sla-status-badge :status="$ticketSla->resolution_status" type="resolution" />
                        </div>
                    </div>
                @else
                    <p class="text-xs text-text-muted">{{ __('sla.indicator.no_policy') }}</p>
                @endif

            </div>
        </div>

        {{-- Actions card --}}
        @auth
            @php
                $showActions =
                    ($ticket->status->value === 'in_progress' && $isAssignedToMe) ||
                    ($ticket->status->value === 'on_hold' && $isAssignedToMe) ||
                    (auth()->user()->can('close', $ticket) && ! $isClosed) ||
                    (auth()->id() === $ticket->requester_id && ! $isClosed);
            @endphp
            @if ($showActions)
                <div class="bg-surface border border-border rounded shadow-sm">
                    <div class="px-4 py-3 border-b border-border">
                        <h2 class="text-xs font-semibold text-text-muted uppercase tracking-widest">{{ __('tickets.show.section_actions') }}</h2>
                    </div>
                    <div class="p-4 space-y-2">

                        @if ($ticket->status->value === 'in_progress' && $isAssignedToMe)
                            <button wire:click="hold" type="button"
                                    class="w-full px-4 py-2.5 text-sm font-medium text-text-base bg-surface border border-border rounded hover:bg-surface-alt transition-colors">
                                {{ __('tickets.show.actions.hold') }}
                            </button>
                            <button wire:click="$dispatch('open-resolve-modal')" type="button"
                                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-success rounded hover:opacity-90 transition-colors">
                                {{ __('tickets.show.actions.resolve') }}
                            </button>
                        @endif

                        @if ($ticket->status->value === 'on_hold' && $isAssignedToMe)
                            <button wire:click="resume" type="button"
                                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-success rounded hover:opacity-90 transition-colors">
                                {{ __('tickets.show.actions.resume') }}
                            </button>
                        @endif

                        @can('close', $ticket)
                            @if (! $isClosed)
                                <div class="space-y-2 {{ ($ticket->status->value === 'in_progress' || $ticket->status->value === 'on_hold') && $isAssignedToMe ? 'pt-2 border-t border-border' : '' }}">
                                    @error('closeReason') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                    @error('closeReasonText') <p class="text-xs text-danger">{{ $message }}</p> @enderror

                                    <select wire:model.live="closeReason"
                                            class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                        <option value="">{{ __('tickets.show.select_close_reason') }}</option>
                                        @foreach (\App\Modules\Tickets\Livewire\ShowTicket::CLOSE_REASONS as $reason)
                                            <option value="{{ $reason }}">{{ __('tickets.close_reasons.' . $reason) }}</option>
                                        @endforeach
                                    </select>

                                    @if ($closeReason === 'other')
                                        <textarea wire:model="closeReasonText" rows="3"
                                                  placeholder="{{ __('tickets.show.close_reason_text_placeholder') }}"
                                                  class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y transition-colors"></textarea>
                                    @endif

                                    <button wire:click="close"
                                            wire:confirm="{{ __('tickets.show.close_confirm') }}"
                                            type="button"
                                            class="w-full px-4 py-2.5 text-sm font-medium text-white bg-danger rounded hover:opacity-90 transition-colors">
                                        {{ __('tickets.show.actions.close') }}
                                    </button>
                                </div>
                            @endif
                        @endcan

                        @if (auth()->id() === $ticket->requester_id && ! $isClosed)
                            <div class="{{ auth()->user()->can('close', $ticket) ? 'pt-2 border-t border-border' : '' }}">
                                <button wire:click="cancel"
                                        wire:confirm="{{ __('tickets.show.cancel_confirm') }}"
                                        type="button"
                                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-danger rounded hover:opacity-90 transition-colors">
                                    {{ __('tickets.show.actions.cancel') }}
                                </button>
                            </div>
                        @endif

                    </div>
                </div>
            @endif
        @endauth

    </aside>

</div>

@auth
    @livewire('precedent.resolve-modal', ['ticket' => $ticket], key('resolve-modal-' . $ticket->id))
@endauth
</div>

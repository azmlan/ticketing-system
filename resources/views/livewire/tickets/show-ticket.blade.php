<div>
    <div class="mb-6">
        <p class="text-sm text-gray-500">{{ $ticket->display_number }}</p>
        <h1 class="text-2xl font-bold">{{ $ticket->subject }}</h1>
        <p class="mt-1">
            {{ __('tickets.show.status_label') }}:
            <span class="font-medium">{{ __('tickets.status.' . $ticket->status->value) }}</span>
        </p>
        @if ($ticket->assignedTo)
            <p>{{ __('tickets.show.assigned_to') }}: {{ $ticket->assignedTo->full_name }}</p>
        @endif
        @if ($ticket->group)
            <p>{{ __('tickets.show.group') }}: {{ $ticket->group->localizedName() }}</p>
        @endif

        @if ($ticketSla)
            <div class="mt-3 flex flex-wrap gap-2 items-center">
                <span class="text-sm text-gray-500 font-medium">{{ __('sla.indicator.title') }}:</span>
                <x-sla-status-badge :status="$ticketSla->response_status" type="response" />
                <x-sla-status-badge :status="$ticketSla->resolution_status" type="resolution" />
            </div>
        @else
            <div class="mt-3">
                <span class="text-sm text-gray-400">{{ __('sla.indicator.no_policy') }}</span>
            </div>
        @endif
    </div>

    <div class="prose max-w-none mb-6">
        {!! $ticket->description !!}
    </div>

    @if ($ticket->attachments->isNotEmpty())
        <div class="mb-6">
            <h3 class="font-semibold mb-2">{{ __('tickets.show.attachments') }}</h3>
            <ul class="space-y-1">
                @foreach ($ticket->attachments as $attachment)
                    <li>
                        <a href="{{ route('tickets.attachments.show', [$ticket->id, $attachment->id]) }}"
                           class="text-blue-600 hover:underline text-sm">
                            {{ $attachment->original_filename }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Custom field values (all, including deactivated/deleted fields per §13.3) --}}
    @if ($customFieldEntries->isNotEmpty())
        <div class="mb-6">
            <h3 class="font-semibold mb-3">{{ __('tickets.show.custom_fields') }}</h3>
            <dl class="divide-y divide-gray-100 rounded-md border border-gray-200">
                @foreach ($customFieldEntries as $entry)
                    <div class="px-4 py-3 flex gap-4">
                        <dt class="text-sm font-medium text-gray-500 w-48 shrink-0 {{ $entry['inactive'] ? 'opacity-60' : '' }}">
                            {{ $entry['label'] }}
                            @if ($entry['inactive'])
                                <span class="ms-1 text-xs text-gray-400">({{ __('tickets.show.field_inactive') }})</span>
                            @endif
                        </dt>
                        <dd class="text-sm text-gray-900 {{ $entry['inactive'] ? 'opacity-60' : '' }}">
                            {{ $entry['value'] !== '' && $entry['value'] !== null ? $entry['value'] : '—' }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    @auth
        @livewire('precedent.auto-suggest-panel', ['ticket' => $ticket], key('auto-suggest-' . $ticket->id))
    @endauth

    <div class="flex flex-wrap gap-3">

        @can('selfAssign', $ticket)
            <button wire:click="selfAssign" type="button"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                {{ __('tickets.show.actions.self_assign') }}
            </button>
        @endcan

        @can('assign', $ticket)
            <div class="flex gap-2">
                <select wire:model="assignToUserId"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('tickets.show.select_tech') }}</option>
                    @foreach ($techs as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                    @endforeach
                </select>
                <button wire:click="managerAssign" type="button"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('tickets.show.actions.assign_to') }}
                </button>
            </div>
        @endcan

        @auth
            @if ($ticket->status->value === 'in_progress' && $ticket->assigned_to === auth()->id())
                <button wire:click="hold" type="button"
                        class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    {{ __('tickets.show.actions.hold') }}
                </button>

                <button wire:click="$dispatch('open-resolve-modal')" type="button"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    {{ __('tickets.show.actions.resolve') }}
                </button>
            @endif

            @if ($ticket->status->value === 'on_hold' && $ticket->assigned_to === auth()->id())
                <button wire:click="resume" type="button"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    {{ __('tickets.show.actions.resume') }}
                </button>
            @endif
        @endauth

        @can('requestTransfer', $ticket)
            @if (! $pendingTransfer)
                <div class="flex gap-2">
                    <select wire:model="transferToUserId"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">{{ __('tickets.show.select_tech') }}</option>
                        @foreach ($techs as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="requestTransfer(transferToUserId)" type="button"
                            class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        {{ __('tickets.show.actions.request_transfer') }}
                    </button>
                </div>
            @endif
        @endcan

        @if ($pendingTransfer && $pendingTransfer->to_user_id === auth()->id())
            <button wire:click="acceptTransfer('{{ $pendingTransfer->id }}')" type="button"
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                {{ __('tickets.show.actions.accept_transfer') }}
            </button>
            <button wire:click="rejectTransfer('{{ $pendingTransfer->id }}')" type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                {{ __('tickets.show.actions.reject_transfer') }}
            </button>
        @endif

        @if ($pendingTransfer && $pendingTransfer->from_user_id === auth()->id())
            <button wire:click="revokeTransfer('{{ $pendingTransfer->id }}')" type="button"
                    class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                {{ __('tickets.show.actions.revoke_transfer') }}
            </button>
        @endif

    </div>

    @can('close', $ticket)
        @if (! in_array($ticket->status->value, ['closed', 'cancelled']))
            <div class="mt-6 border-t pt-4">
                <h3 class="font-semibold mb-2">{{ __('tickets.show.actions.close') }}</h3>

                @error('closeReason') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror
                @error('closeReasonText') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror

                <div class="flex flex-col gap-2 max-w-md">
                    <select wire:model.live="closeReason"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">{{ __('tickets.show.select_close_reason') }}</option>
                        @foreach (\App\Modules\Tickets\Livewire\ShowTicket::CLOSE_REASONS as $reason)
                            <option value="{{ $reason }}">{{ __('tickets.close_reasons.' . $reason) }}</option>
                        @endforeach
                    </select>

                    @if ($closeReason === 'other')
                        <textarea wire:model="closeReasonText" rows="3"
                                  placeholder="{{ __('tickets.show.close_reason_text_placeholder') }}"
                                  class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    @endif

                    <button wire:click="close"
                            wire:confirm="{{ __('tickets.show.close_confirm') }}"
                            type="button"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        {{ __('tickets.show.actions.close') }}
                    </button>
                </div>
            </div>
        @endif
    @endcan

    @if (auth()->id() === $ticket->requester_id && ! in_array($ticket->status->value, ['closed', 'cancelled']))
        <div class="mt-4">
            <button wire:click="cancel"
                    wire:confirm="{{ __('tickets.show.cancel_confirm') }}"
                    type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                {{ __('tickets.show.actions.cancel') }}
            </button>
        </div>
    @endif

    @auth
        @if (auth()->user()->is_tech && $ticket->status->value === 'in_progress')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.submit-condition-report', ['ticketId' => $ticket->id], key('condition-report-' . $ticket->id))
            </div>
        @endif
    @endauth

    @can('escalation.approve')
        @if ($ticket->status->value === 'awaiting_approval')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.review-condition-report', ['ticketId' => $ticket->id], key('review-cr-' . $ticket->id))
            </div>
        @endif
    @endcan

    @if ($ticket->status->value === 'action_required' && auth()->id() === $ticket->requester_id)
        <div class="mt-8 border-t pt-6">
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
                <div class="mt-8 border-t pt-6">
                    <h3 class="font-semibold mb-3">{{ __('escalation.maintenance_request.title') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'ar']) }}"
                           class="inline-block rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            {{ __('escalation.maintenance_request.export_ar') }}
                        </a>
                        <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'en']) }}"
                           class="inline-block rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                            {{ __('escalation.maintenance_request.export_en') }}
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    @endif

    @can('escalation.approve')
        @if ($ticket->status->value === 'awaiting_final_approval')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.review-signed-maintenance-request', ['ticketId' => $ticket->id], key('review-signed-' . $ticket->id))
            </div>
        @endif
    @endcan

    @auth
        @livewire('csat.rating-section', ['ticketId' => $ticket->id], key('csat-' . $ticket->id))
    @endauth

    <div class="mt-8 border-t pt-6">
        @livewire('communication.add-comment', ['ticketUlid' => $ticket->id], key('comments-' . $ticket->id))
    </div>

    @auth
        @livewire('precedent.resolve-modal', ['ticket' => $ticket], key('resolve-modal-' . $ticket->id))
    @endauth
</div>

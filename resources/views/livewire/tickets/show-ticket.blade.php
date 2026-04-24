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

        {{-- SLA Indicator --}}
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

    {{-- Attachments --}}
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

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">

        {{-- Self-assign --}}
        @can('selfAssign', $ticket)
            <button wire:click="selfAssign" type="button">
                {{ __('tickets.show.actions.self_assign') }}
            </button>
        @endcan

        {{-- Manager / IT-manager assign --}}
        @can('assign', $ticket)
            <div class="flex gap-2">
                <select wire:model="assignToUserId">
                    <option value="">{{ __('tickets.show.select_tech') }}</option>
                    @foreach ($techs as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                    @endforeach
                </select>
                <button wire:click="managerAssign(assignToUserId)" type="button">
                    {{ __('tickets.show.actions.assign_to') }}
                </button>
            </div>
        @endcan

        {{-- Put on hold --}}
        @auth
            @if ($ticket->status->value === 'in_progress' && $ticket->assigned_to === auth()->id())
                <button wire:click="hold" type="button">
                    {{ __('tickets.show.actions.hold') }}
                </button>
            @endif

            {{-- Resume from hold --}}
            @if ($ticket->status->value === 'on_hold' && $ticket->assigned_to === auth()->id())
                <button wire:click="resume" type="button">
                    {{ __('tickets.show.actions.resume') }}
                </button>
            @endif
        @endauth

        {{-- Request transfer --}}
        @can('requestTransfer', $ticket)
            @if (! $pendingTransfer)
                <div class="flex gap-2">
                    <select wire:model="transferToUserId">
                        <option value="">{{ __('tickets.show.select_tech') }}</option>
                        @foreach ($techs as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="requestTransfer(transferToUserId)" type="button">
                        {{ __('tickets.show.actions.request_transfer') }}
                    </button>
                </div>
            @endif
        @endcan

        {{-- Accept / Reject transfer (target tech) --}}
        @if ($pendingTransfer && $pendingTransfer->to_user_id === auth()->id())
            <button wire:click="acceptTransfer('{{ $pendingTransfer->id }}')" type="button">
                {{ __('tickets.show.actions.accept_transfer') }}
            </button>
            <button wire:click="rejectTransfer('{{ $pendingTransfer->id }}')" type="button">
                {{ __('tickets.show.actions.reject_transfer') }}
            </button>
        @endif

        {{-- Revoke transfer (requesting tech) --}}
        @if ($pendingTransfer && $pendingTransfer->from_user_id === auth()->id())
            <button wire:click="revokeTransfer('{{ $pendingTransfer->id }}')" type="button">
                {{ __('tickets.show.actions.revoke_transfer') }}
            </button>
        @endif

    </div>

    {{-- Close ticket (permission:ticket.close) --}}
    @can('close', $ticket)
        @if (! in_array($ticket->status->value, ['closed', 'cancelled']))
            <div class="mt-6 border-t pt-4">
                <h3 class="font-semibold mb-2">{{ __('tickets.show.actions.close') }}</h3>

                @error('closeReason') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror
                @error('closeReasonText') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror

                <div class="flex flex-col gap-2 max-w-md">
                    <select wire:model.live="closeReason">
                        <option value="">{{ __('tickets.show.select_close_reason') }}</option>
                        @foreach (\App\Modules\Tickets\Livewire\ShowTicket::CLOSE_REASONS as $reason)
                            <option value="{{ $reason }}">{{ __('tickets.close_reasons.' . $reason) }}</option>
                        @endforeach
                    </select>

                    @if ($closeReason === 'other')
                        <textarea wire:model="closeReasonText"
                                  rows="3"
                                  placeholder="{{ __('tickets.show.close_reason_text_placeholder') }}"></textarea>
                    @endif

                    <button wire:click="close"
                            wire:confirm="{{ __('tickets.show.close_confirm') }}"
                            type="button">
                        {{ __('tickets.show.actions.close') }}
                    </button>
                </div>
            </div>
        @endif
    @endcan

    {{-- Cancel ticket (requester only) --}}
    @if (auth()->id() === $ticket->requester_id && ! in_array($ticket->status->value, ['closed', 'cancelled']))
        <div class="mt-4">
            <button wire:click="cancel"
                    wire:confirm="{{ __('tickets.show.cancel_confirm') }}"
                    type="button">
                {{ __('tickets.show.actions.cancel') }}
            </button>
        </div>
    @endif

    {{-- Condition Report (tech only, in_progress tickets) --}}
    @auth
        @if (auth()->user()->is_tech && $ticket->status->value === 'in_progress')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.submit-condition-report', ['ticketId' => $ticket->id], key('condition-report-' . $ticket->id))
            </div>
        @endif
    @endauth

    {{-- Review Condition Report (escalation.approve, awaiting_approval tickets) --}}
    @can('escalation.approve')
        @if ($ticket->status->value === 'awaiting_approval')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.review-condition-report', ['ticketId' => $ticket->id], key('review-cr-' . $ticket->id))
            </div>
        @endif
    @endcan

    {{-- Requester: upload signed maintenance request (action_required) --}}
    @if ($ticket->status->value === 'action_required' && auth()->id() === $ticket->requester_id)
        <div class="mt-8 border-t pt-6">
            @livewire('escalation.upload-signed-maintenance-request', ['ticketId' => $ticket->id], key('upload-signed-' . $ticket->id))
        </div>
    @endif

    {{-- Tech / approver: download buttons only (action_required, not requester) --}}
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
                        <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'ar']) }}">
                            {{ __('escalation.maintenance_request.export_ar') }}
                        </a>
                        <a href="{{ route('escalation.maintenance-request.download', [$ticket->id, 'en']) }}">
                            {{ __('escalation.maintenance_request.export_en') }}
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    @endif

    {{-- Approver: final review (awaiting_final_approval) --}}
    @can('escalation.approve')
        @if ($ticket->status->value === 'awaiting_final_approval')
            <div class="mt-8 border-t pt-6">
                @livewire('escalation.review-signed-maintenance-request', ['ticketId' => $ticket->id], key('review-signed-' . $ticket->id))
            </div>
        @endif
    @endcan

    {{-- Comments --}}
    <div class="mt-8 border-t pt-6">
        @livewire('communication.add-comment', ['ticketUlid' => $ticket->id], key('comments-' . $ticket->id))
    </div>
</div>

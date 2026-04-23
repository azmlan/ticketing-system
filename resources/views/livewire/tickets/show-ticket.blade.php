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
</div>

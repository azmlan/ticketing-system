<div>
    <h3 class="font-semibold text-lg mb-4">{{ __('escalation.final_review.title') }}</h3>

    {{-- Signed document link --}}
    @if ($mr->submitted_file_path)
        <div class="mb-4">
            <a href="{{ route('escalation.signed-document.show', $mr->id) }}"
               class="text-blue-600 hover:underline">
                {{ __('escalation.final_review.signed_document') }}
            </a>
        </div>
    @endif

    {{-- Rejection count --}}
    @if ($mr->rejection_count > 0)
        <div class="mb-2 text-sm text-orange-700">
            {{ __('escalation.final_review.rejection_count') }}: {{ $mr->rejection_count }}
        </div>
    @endif

    {{-- Prior rejection notes --}}
    @if ($mr->rejection_count > 0 && $mr->review_notes)
        <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded text-sm">
            <p class="font-medium mb-1">{{ __('escalation.final_review.prior_notes') }}</p>
            <p>{{ $mr->review_notes }}</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3 mt-4">

        {{-- Approve --}}
        @if (! $showRejectForm && ! $showPermanentForm)
            <button wire:click="approve"
                    wire:confirm="{{ __('escalation.final_review.approve_confirm') }}"
                    type="button">
                {{ __('escalation.final_review.approve') }}
            </button>

            <button wire:click="$set('showRejectForm', true)" type="button">
                {{ __('escalation.final_review.reject_resubmit') }}
            </button>

            <button wire:click="$set('showPermanentForm', true)" type="button">
                {{ __('escalation.final_review.reject_permanently') }}
            </button>
        @endif

    </div>

    {{-- Reject (resubmit) form --}}
    @if ($showRejectForm)
        <div class="mt-4 border-t pt-4">
            <h4 class="font-medium mb-2">{{ __('escalation.final_review.reject_resubmit') }}</h4>

            @error('reviewNotes') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror

            <textarea wire:model="reviewNotes"
                      rows="4"
                      placeholder="{{ __('escalation.final_review.review_notes_placeholder') }}"
                      class="w-full border rounded p-2 mb-3"></textarea>

            <div class="flex gap-2">
                <button wire:click="rejectResubmit" type="button">
                    {{ __('escalation.final_review.submit_rejection') }}
                </button>
                <button wire:click="$set('showRejectForm', false)" type="button">
                    {{ __('escalation.final_review.cancel') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Reject permanently form --}}
    @if ($showPermanentForm)
        <div class="mt-4 border-t pt-4">
            <h4 class="font-medium mb-2">{{ __('escalation.final_review.reject_permanently') }}</h4>

            @error('closeReason') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror
            @error('closeReasonText') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror

            <select wire:model.live="closeReason" class="mb-3 border rounded p-2">
                <option value="">{{ __('tickets.show.select_close_reason') }}</option>
                @foreach ($closeReasons as $reason)
                    <option value="{{ $reason }}">{{ __('tickets.close_reasons.' . $reason) }}</option>
                @endforeach
            </select>

            @if ($closeReason === 'other')
                <textarea wire:model="closeReasonText"
                          rows="3"
                          placeholder="{{ __('tickets.show.close_reason_text_placeholder') }}"
                          class="w-full border rounded p-2 mb-3"></textarea>
            @endif

            <div class="flex gap-2">
                <button wire:click="rejectPermanently" type="button">
                    {{ __('escalation.final_review.submit_permanent') }}
                </button>
                <button wire:click="$set('showPermanentForm', false)" type="button">
                    {{ __('escalation.final_review.cancel') }}
                </button>
            </div>
        </div>
    @endif
</div>

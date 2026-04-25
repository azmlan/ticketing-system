<div>
    <h2 class="text-xl font-bold mb-4">{{ __('escalation.review.title') }}</h2>

    <div class="space-y-4 mb-6">
        <div>
            <span class="font-semibold">{{ __('escalation.condition_report.report_type') }}:</span>
            {{ $report->report_type }}
        </div>

        @if ($report->location)
            <div>
                <span class="font-semibold">{{ __('escalation.condition_report.location') }}:</span>
                {{ $report->location->localizedName() }}
            </div>
        @endif

        <div>
            <span class="font-semibold">{{ __('escalation.review.report_date') }}:</span>
            {{ $report->report_date->format('Y-m-d') }}
        </div>

        <div>
            <span class="font-semibold">{{ __('escalation.review.submitted_by') }}:</span>
            {{ $report->tech->full_name }}
        </div>

        <div>
            <span class="font-semibold">{{ __('escalation.review.submitted_at') }}:</span>
            {{ $report->created_at->format('Y-m-d H:i') }}
        </div>

        <div>
            <span class="font-semibold">{{ __('escalation.condition_report.current_condition') }}:</span>
            <div class="prose max-w-none mt-1">{!! $currentCondition !!}</div>
        </div>

        <div>
            <span class="font-semibold">{{ __('escalation.condition_report.condition_analysis') }}:</span>
            <div class="prose max-w-none mt-1">{!! $conditionAnalysis !!}</div>
        </div>

        <div>
            <span class="font-semibold">{{ __('escalation.condition_report.required_action') }}:</span>
            <div class="prose max-w-none mt-1">{!! $requiredAction !!}</div>
        </div>
    </div>

    @if ($report->attachments->isNotEmpty())
        <div class="mb-6">
            <h3 class="font-semibold mb-2">{{ __('escalation.condition_report.attachments') }}</h3>
            <ul class="space-y-1">
                @foreach ($report->attachments as $attachment)
                    <li>
                        <a href="{{ route('escalation.condition-report-attachments.show', [$report->id, $attachment->id]) }}"
                           class="text-blue-600 hover:underline text-sm">
                            {{ $attachment->original_name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <button wire:click="approve"
                wire:confirm="{{ __('escalation.review.approve_confirm') }}"
                type="button"
                class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            {{ __('escalation.review.approve') }}
        </button>

        @if (! $showRejectForm)
            <button wire:click="$set('showRejectForm', true)" type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                {{ __('escalation.review.reject') }}
            </button>
        @endif
    </div>

    @if ($showRejectForm)
        <div class="mt-4 border-t pt-4">
            <h3 class="font-semibold mb-2">{{ __('escalation.review.reject_with_notes') }}</h3>

            @error('reviewNotes') <p class="text-red-600 text-sm mb-1">{{ $message }}</p> @enderror

            <div class="flex flex-col gap-2 max-w-md">
                <textarea wire:model="reviewNotes"
                          rows="4"
                          placeholder="{{ __('escalation.review.review_notes_placeholder') }}"
                          class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>

                <div class="flex gap-2">
                    <button wire:click="reject" type="button"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        {{ __('escalation.review.submit_rejection') }}
                    </button>
                    <button wire:click="$set('showRejectForm', false)" type="button"
                            class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        {{ __('escalation.review.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

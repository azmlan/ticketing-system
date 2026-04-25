<div>
    @if ($viewMode === 'none')
        {{-- Nothing to show --}}
    @elseif ($viewMode === 'pending_form')
        <div class="mt-8 border-t pt-6">
            <h3 class="font-semibold mb-3">{{ __('csat.section.title') }}</h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ __('csat.section.pending', ['tech' => $csatRating->tech->full_name]) }}
            </p>

            {{-- Star rating --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('csat.section.rating_label') }}</label>
                <div class="flex gap-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button"
                                wire:click="$set('rating', {{ $i }})"
                                class="text-2xl focus:outline-none {{ $rating >= $i ? 'text-yellow-400' : 'text-gray-300' }}">
                            ★
                        </button>
                    @endfor
                </div>
                @error('rating')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Comment --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">{{ __('csat.section.comment_label') }}</label>
                <textarea wire:model="comment"
                          rows="3"
                          class="w-full border rounded px-3 py-2 text-sm"
                          maxlength="1000"></textarea>
            </div>

            <button type="button"
                    wire:click="submit"
                    class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
                {{ __('csat.prompt.submit') }}
            </button>
        </div>

    @elseif ($viewMode === 'read_only')
        <div class="mt-8 border-t pt-6">
            <h3 class="font-semibold mb-3">{{ __('csat.section.title') }}</h3>

            @if ($csatRating->rating)
                <div class="flex items-center gap-1 mb-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <span class="text-2xl {{ $i <= $csatRating->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                    @endfor
                    <span class="text-sm text-gray-500 ms-2">{{ $csatRating->rating }}/5</span>
                </div>

                @if ($csatRating->comment)
                    <p class="text-sm text-gray-700 mb-1">
                        <span class="font-medium">{{ __('csat.section.comment_label') }}:</span>
                        {{ $csatRating->comment }}
                    </p>
                @endif

                <p class="text-xs text-gray-400">
                    {{ __('csat.section.submitted_at') }}: {{ $csatRating->submitted_at->translatedFormat('d M Y') }}
                </p>
            @else
                <p class="text-sm text-gray-500">{{ __('csat.section.no_rating') }}</p>
            @endif
        </div>
    @endif
</div>

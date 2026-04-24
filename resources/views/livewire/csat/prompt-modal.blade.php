<div>
    @if ($visible && $csatRating)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
             role="dialog" aria-modal="true" aria-labelledby="csat-modal-title">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">

                <h2 id="csat-modal-title" class="text-lg font-semibold mb-1">
                    {{ __('csat.prompt.title') }}
                </h2>

                <p class="text-sm text-gray-600 mb-4">
                    {{ __('csat.prompt.intro', [
                        'tech'   => $csatRating->tech->full_name,
                        'number' => $csatRating->ticket->display_number,
                    ]) }}
                </p>

                <p class="text-sm text-gray-500 mb-4">
                    <span class="font-medium">{{ __('csat.prompt.subject_label') }}:</span>
                    {{ $csatRating->ticket->subject }}
                </p>

                {{-- Star rating --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">{{ __('csat.prompt.rating_label') }}</label>
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
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-1">{{ __('csat.prompt.comment_label') }}</label>
                    <textarea wire:model="comment"
                              rows="3"
                              class="w-full border rounded px-3 py-2 text-sm"
                              maxlength="1000"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            wire:click="dismiss"
                            class="text-sm text-gray-500 hover:text-gray-700">
                        {{ __('csat.prompt.dismiss') }}
                    </button>
                    <button type="button"
                            wire:click="submit"
                            class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
                        {{ __('csat.prompt.submit') }}
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>

<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('precedent.resolve_modal.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('precedent.resolve_modal.subtitle') }}</p>
                </div>

                <form wire:submit="submit" class="px-6 py-4 space-y-4">

                    {{-- Summary --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('precedent.resolve_modal.summary') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="summary" type="text" maxlength="500"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        @error('summary')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Root Cause --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('precedent.resolve_modal.root_cause') }}
                        </label>
                        <input wire:model="rootCause" type="text" maxlength="500"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        @error('rootCause')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Steps Taken (rich text) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('precedent.resolve_modal.steps_taken') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="stepsTaken" rows="5"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        @error('stepsTaken')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parts / Resources --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('precedent.resolve_modal.parts_resources') }}
                        </label>
                        <textarea wire:model="partsResources" rows="2"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        @error('partsResources')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Time Spent + Resolution Type --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('precedent.resolve_modal.time_spent_minutes') }}
                            </label>
                            <input wire:model="timeSpentMinutes" type="number" min="1"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            @error('timeSpentMinutes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('precedent.resolve_modal.resolution_type') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="resolutionType"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">{{ __('precedent.resolve_modal.select_type') }}</option>
                                @foreach (['known_fix', 'workaround', 'escalated_externally', 'other'] as $type)
                                    <option value="{{ $type }}">{{ __('precedent.resolution_type.' . $type) }}</option>
                                @endforeach
                            </select>
                            @error('resolutionType')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4">
                        <button type="button" wire:click="cancel"
                                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            {{ __('precedent.resolve_modal.cancel') }}
                        </button>
                        <button type="submit"
                                class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            {{ __('precedent.resolve_modal.submit') }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif
</div>

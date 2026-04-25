<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('precedent.resolve_modal.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('precedent.resolve_modal.subtitle') }}</p>
                </div>

                <form wire:submit="submit" class="px-6 py-4 space-y-4">

                    {{-- Mode toggle --}}
                    <div class="flex overflow-hidden rounded-md border border-gray-200">
                        <button type="button" wire:click="switchMode('write')"
                                @class([
                                    'flex-1 px-4 py-2 text-sm font-medium transition-colors',
                                    'bg-blue-600 text-white'          => $mode === 'write',
                                    'bg-white text-gray-700 hover:bg-gray-50' => $mode !== 'write',
                                ])>
                            {{ __('precedent.resolve_modal.write_new') }}
                        </button>
                        <button type="button" wire:click="switchMode('link')"
                                @class([
                                    'flex-1 border-s border-gray-200 px-4 py-2 text-sm font-medium transition-colors',
                                    'bg-blue-600 text-white'          => $mode === 'link',
                                    'bg-white text-gray-700 hover:bg-gray-50' => $mode !== 'link',
                                ])>
                            {{ __('precedent.resolve_modal.link_existing') }}
                        </button>
                    </div>

                    {{-- Summary --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
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
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            {{ __('precedent.resolve_modal.root_cause') }}
                        </label>
                        <input wire:model="rootCause" type="text" maxlength="500"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        @error('rootCause')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Write mode: Steps Taken --}}
                    @if ($mode === 'write')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ __('precedent.resolve_modal.steps_taken') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea wire:model="stepsTaken" rows="5"
                                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            @error('stepsTaken')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Link mode: search + selection + link notes --}}
                    @if ($mode === 'link')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ __('precedent.resolve_modal.link_existing') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input wire:model.live.debounce.300ms="searchQuery"
                                   type="text"
                                   placeholder="{{ __('precedent.resolve_modal.search_resolutions') }}"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />

                            @if (mb_strlen(trim($searchQuery)) >= 2)
                                <div class="mt-1 max-h-48 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-sm">
                                    @forelse ($this->searchResults as $res)
                                        <button type="button"
                                                wire:click="selectResolution('{{ $res->id }}')"
                                                wire:key="res-{{ $res->id }}"
                                                class="w-full border-b border-gray-100 px-3 py-2 text-start last:border-0 hover:bg-gray-50">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-medium text-gray-900 truncate">{{ $res->summary }}</span>
                                                <span class="shrink-0 text-xs text-gray-400">{{ $res->usage_count }} {{ __('precedent.auto_suggest.usage_count') }}</span>
                                            </div>
                                            <span class="text-xs text-gray-500">{{ __('precedent.resolution_type.' . $res->resolution_type) }}</span>
                                        </button>
                                    @empty
                                        <p class="px-3 py-2 text-sm text-gray-500">{{ __('precedent.resolve_modal.no_results') }}</p>
                                    @endforelse
                                </div>
                            @endif

                            @if ($this->selectedResolution)
                                <div class="mt-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-blue-900">{{ __('precedent.resolve_modal.selected_resolution') }}</span>
                                        <button type="button" wire:click="clearLinkedResolution"
                                                class="text-blue-400 hover:text-blue-600 text-lg leading-none">&times;</button>
                                    </div>
                                    <p class="mt-1 text-sm text-blue-800">{{ $this->selectedResolution->summary }}</p>
                                    <p class="mt-0.5 text-xs text-blue-600">{{ __('precedent.resolution_type.' . $this->selectedResolution->resolution_type) }}</p>
                                </div>
                            @endif

                            @error('linkedResolutionId')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ __('precedent.resolve_modal.link_notes') }}
                            </label>
                            <textarea wire:model="linkNotes" rows="2"
                                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            @error('linkNotes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Parts / Resources --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
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
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ __('precedent.resolve_modal.time_spent_minutes') }}
                            </label>
                            <input wire:model="timeSpentMinutes" type="number" min="1"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                            @error('timeSpentMinutes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
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

<div class="mb-6 rounded-lg border border-blue-200 bg-blue-50">
    {{-- Header / toggle --}}
    <button type="button" wire:click="toggle"
            class="flex w-full items-center justify-between px-4 py-3 text-start">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-blue-900">{{ __('precedent.auto_suggest.title') }}</span>
            @if ($this->suggestions->isNotEmpty())
                <span class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-medium text-white">
                    {{ $this->suggestions->count() }}
                </span>
            @endif
        </div>
        <svg class="h-4 w-4 text-blue-600 transition-transform {{ $collapsed ? '' : 'rotate-180' }}"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
        </svg>
    </button>

    @unless ($collapsed)
        <div class="border-t border-blue-200 px-4 py-3">
            @forelse ($this->suggestions as $suggestion)
                @php
                    $badgeClass = match($suggestion->resolution_type) {
                        'known_fix'            => 'bg-green-100 text-green-800',
                        'workaround'           => 'bg-yellow-100 text-yellow-800',
                        'escalated_externally' => 'bg-red-100 text-red-800',
                        default                => 'bg-gray-100 text-gray-800',
                    };
                @endphp

                <div class="mb-4 rounded-md border border-gray-200 bg-white p-4 last:mb-0 shadow-sm">

                    {{-- Summary + badges --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900">{{ $suggestion->summary }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                            {{ __('precedent.resolution_type.' . $suggestion->resolution_type) }}
                        </span>
                        @if ($suggestion->usage_count > 0)
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                {{ $suggestion->usage_count }} {{ __('precedent.auto_suggest.usage_count') }}
                            </span>
                        @endif
                    </div>

                    {{-- Resolved date --}}
                    @if ($suggestion->ticket?->resolved_at)
                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('precedent.auto_suggest.resolved_on') }}:
                            {{ $suggestion->ticket->resolved_at->toDateString() }}
                        </p>
                    @endif

                    {{-- Steps taken (truncated) --}}
                    @if ($suggestion->steps_taken)
                        <p class="mt-2 text-sm text-gray-700">
                            {{ \Illuminate\Support\Str::limit(strip_tags($suggestion->steps_taken), 200) }}
                        </p>
                    @elseif ($suggestion->linkedResolution)
                        <p class="mt-2 text-xs text-gray-400 italic">
                            {{ __('precedent.resolve_modal.link_existing') }}
                        </p>
                    @endif

                    {{-- Custom field context from source ticket --}}
                    @if ($suggestion->ticket?->customFieldValues->isNotEmpty())
                        <div class="mt-3 border-t border-gray-100 pt-2">
                            <p class="mb-1 text-xs font-medium text-gray-400">
                                {{ __('precedent.auto_suggest.context_fields') }}
                            </p>
                            <dl class="space-y-0.5">
                                @foreach ($suggestion->ticket->customFieldValues as $cfv)
                                    @if ($cfv->value !== null && $cfv->value !== '')
                                        <div class="flex gap-2 text-xs text-gray-500">
                                            <dt class="shrink-0 font-medium">{{ $cfv->field?->localizedName() }}:</dt>
                                            <dd>{{ $cfv->value }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('precedent.auto_suggest.empty') }}</p>
            @endforelse
        </div>
    @endunless
</div>

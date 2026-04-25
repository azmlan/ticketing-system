<div class="rounded border border-primary-200 bg-primary-50">
    {{-- Header / toggle --}}
    <button type="button" wire:click="toggle"
            class="flex w-full items-center justify-between px-4 py-3 text-start">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-primary-800">{{ __('precedent.auto_suggest.title') }}</span>
            @if ($this->suggestions->isNotEmpty())
                <span class="rounded-full bg-primary-500 px-2 py-0.5 text-xs font-medium text-white">
                    {{ $this->suggestions->count() }}
                </span>
            @endif
        </div>
        <svg class="h-4 w-4 text-primary-500 transition-transform {{ $collapsed ? '' : 'rotate-180' }}"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
        </svg>
    </button>

    @unless ($collapsed)
        <div class="border-t border-primary-200 px-4 py-3">
            @forelse ($this->suggestions as $suggestion)
                @php
                    $resTypeBadge = match($suggestion->resolution_type) {
                        'known_fix'            => 'bg-success/10 text-success',
                        'workaround'           => 'bg-warning/10 text-warning',
                        'escalated_externally' => 'bg-danger/10 text-danger',
                        default                => 'bg-surface-alt text-text-secondary',
                    };
                @endphp

                <div class="mb-4 rounded border border-border bg-surface p-4 last:mb-0 shadow-sm">

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-text-base">{{ $suggestion->summary }}</span>
                        <span class="rounded px-2 py-0.5 text-xs font-medium {{ $resTypeBadge }}">
                            {{ __('precedent.resolution_type.' . $suggestion->resolution_type) }}
                        </span>
                        @if ($suggestion->usage_count > 0)
                            <span class="rounded px-2 py-0.5 text-xs font-medium bg-primary-50 text-primary-500">
                                {{ $suggestion->usage_count }} {{ __('precedent.auto_suggest.usage_count') }}
                            </span>
                        @endif
                    </div>

                    @if ($suggestion->ticket?->resolved_at)
                        <p class="mt-1 text-xs text-text-muted">
                            {{ __('precedent.auto_suggest.resolved_on') }}:
                            {{ $suggestion->ticket->resolved_at->toDateString() }}
                        </p>
                    @endif

                    @if ($suggestion->steps_taken)
                        <p class="mt-2 text-sm text-text-secondary">
                            {{ \Illuminate\Support\Str::limit(strip_tags($suggestion->steps_taken), 200) }}
                        </p>
                    @elseif ($suggestion->linkedResolution)
                        <p class="mt-2 text-xs text-text-muted italic">
                            {{ __('precedent.resolve_modal.link_existing') }}
                        </p>
                    @endif

                    @if ($suggestion->ticket?->customFieldValues->isNotEmpty())
                        <div class="mt-3 border-t border-border pt-2">
                            <p class="mb-1 text-xs font-medium text-text-muted">
                                {{ __('precedent.auto_suggest.context_fields') }}
                            </p>
                            <dl class="space-y-0.5">
                                @foreach ($suggestion->ticket->customFieldValues as $cfv)
                                    @if ($cfv->value !== null && $cfv->value !== '')
                                        <div class="flex gap-2 text-xs text-text-secondary">
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
                <p class="text-sm text-text-muted">{{ __('precedent.auto_suggest.empty') }}</p>
            @endforelse
        </div>
    @endunless
</div>

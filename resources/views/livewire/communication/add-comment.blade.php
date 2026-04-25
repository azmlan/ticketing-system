<div>
    {{-- Comment list (chronological — oldest first, newest at bottom) --}}
    @if ($comments->isNotEmpty())
        <div class="space-y-4 mb-6">
            @foreach ($comments as $comment)
                <div class="flex gap-3">
                    {{-- Avatar initial --}}
                    <div class="w-8 h-8 rounded-full {{ $comment->is_internal ? 'bg-warning' : 'bg-primary-500' }} flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                        {{ mb_strtoupper(mb_substr($comment->author->full_name, 0, 1)) }}
                    </div>
                    {{-- Comment body --}}
                    <div class="flex-1 min-w-0">
                        <div class="{{ $comment->is_internal ? 'border-s-4 border-warning bg-warning/5' : 'bg-surface border border-border' }} rounded px-4 py-3">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-sm font-semibold text-text-base">{{ $comment->author->full_name }}</span>
                                <span class="text-xs text-text-muted">{{ $comment->created_at->diffForHumans() }}</span>
                                @if ($comment->is_internal)
                                    <span class="text-xs font-medium bg-warning/10 text-warning px-2 py-0.5 rounded">
                                        {{ __('communication.comments.internal_badge') }}
                                    </span>
                                @endif
                            </div>
                            <div class="prose max-w-none text-sm text-text-base">
                                {!! $comment->body !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add comment form --}}
    @auth
        <div class="{{ $comments->isNotEmpty() ? 'border-t border-border pt-6' : '' }}">
            <h3 class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-4">{{ __('communication.comments.section_title') }}</h3>

            <form wire:submit="submit" novalidate>
                @csrf

                @if (auth()->user()->is_tech || auth()->user()->is_super_user)
                    <div class="flex flex-col gap-1.5 mb-4">
                        <label class="text-sm font-medium text-text-secondary">{{ __('communication.comments.template_label') }}</label>
                        <select wire:model.live="selectedTemplate"
                                class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                            <option value="">{{ __('communication.comments.select_template') }}</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl['id'] }}">{{ $tpl['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 mb-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="isInternal"
                                   class="h-4 w-4 rounded border-border text-primary-500 focus:ring-primary-500">
                            <span class="text-sm text-text-secondary">{{ __('communication.comments.internal_label') }}</span>
                        </label>
                    </div>
                @endif

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-sm font-medium text-text-secondary">
                        {{ __('communication.comments.body_label') }} <span class="text-danger">*</span>
                    </label>
                    <textarea wire:model="body"
                              rows="4"
                              maxlength="10000"
                              placeholder="{{ __('communication.comments.body_placeholder') }}"
                              required
                              class="w-full px-3 py-2.5 text-sm border border-border rounded bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y transition-colors"></textarea>
                    @error('body')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2.5 text-sm font-medium text-white bg-primary-500 rounded hover:bg-primary-600 transition-colors">
                        {{ __('communication.comments.submit') }}
                    </button>
                </div>
            </form>
        </div>
    @endauth
</div>

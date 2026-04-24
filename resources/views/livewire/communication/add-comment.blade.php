<div>
    {{-- Comment form --}}
    @auth
        <div class="mb-6">
            <h3 class="font-semibold mb-4">{{ __('communication.comments.section_title') }}</h3>

            <form wire:submit="submit" novalidate>
                @csrf

                {{-- Response template selector (techs / super-users only) --}}
                @if (auth()->user()->is_tech || auth()->user()->is_super_user)
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">
                            {{ __('communication.comments.template_label') }}
                        </label>
                        <select wire:model.live="selectedTemplate" class="w-full">
                            <option value="">{{ __('communication.comments.select_template') }}</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl['id'] }}">{{ $tpl['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Internal / public toggle --}}
                    <div class="mb-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="isInternal">
                            <span class="text-sm">{{ __('communication.comments.internal_label') }}</span>
                        </label>
                    </div>
                @endif

                {{-- Body --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        {{ __('communication.comments.body_label') }}
                    </label>
                    <textarea wire:model="body"
                              rows="4"
                              maxlength="10000"
                              placeholder="{{ __('communication.comments.body_placeholder') }}"
                              class="w-full"
                              required></textarea>
                    @error('body')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit">{{ __('communication.comments.submit') }}</button>
            </form>
        </div>
    @endauth

    {{-- Comment timeline --}}
    @if ($comments->isNotEmpty())
        <div class="mt-4 space-y-4">
            @foreach ($comments as $comment)
                <div class="{{ $comment->is_internal ? 'bg-yellow-50 border-s-4 border-yellow-400' : 'bg-white border border-gray-200' }} rounded p-4">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <span class="font-semibold text-sm">{{ $comment->author->full_name }}</span>
                        <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                        @if ($comment->is_internal)
                            <span class="text-xs font-medium bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded">
                                {{ __('communication.comments.internal_badge') }}
                            </span>
                        @endif
                    </div>
                    <div class="prose max-w-none text-sm">
                        {!! $comment->body !!}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

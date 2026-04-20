<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.password_request.title') }}</h1>

    @if($sent)
        <p class="text-green-700">{{ __('auth.password_reset_sent') }}</p>
    @else
        <form wire:submit="sendResetLink" novalidate>
            @csrf

            <div class="mb-4">
                <label for="email">{{ __('auth.password_request.email') }}</label>
                <input id="email" type="email" wire:model="email" required autocomplete="email">
                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <button type="submit">{{ __('auth.password_request.submit') }}</button>
        </form>
    @endif

    <p class="mt-4 text-sm">
        <a href="{{ route('login') }}">{{ __('auth.password_request.back_login') }}</a>
    </p>
</div>

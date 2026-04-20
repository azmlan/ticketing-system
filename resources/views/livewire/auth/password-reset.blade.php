<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.password_reset.title') }}</h1>

    <form wire:submit="resetPassword" novalidate>
        @csrf

        <input type="hidden" wire:model="token">
        <input type="hidden" wire:model="email">

        <div class="mb-4">
            <label for="email_display">{{ __('auth.password_reset.email') }}</label>
            <input id="email_display" type="email" value="{{ $email }}" readonly>
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password">{{ __('auth.password_reset.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="new-password">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation">{{ __('auth.password_reset.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit">{{ __('auth.password_reset.submit') }}</button>
    </form>
</div>

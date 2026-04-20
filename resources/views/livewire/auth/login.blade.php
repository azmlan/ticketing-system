<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.login.title') }}</h1>

    <form wire:submit="login" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email">{{ __('auth.login.email') }}</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password">{{ __('auth.login.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="current-password">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4 flex items-center gap-2">
            <input id="remember" type="checkbox" wire:model="remember">
            <label for="remember">{{ __('auth.login.remember') }}</label>
        </div>

        <button type="submit">{{ __('auth.login.submit') }}</button>
    </form>

    <p class="mt-4 text-sm">
        <a href="{{ route('password.request') }}">{{ __('auth.login.forgot') }}</a>
    </p>

    <p class="mt-2 text-sm">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}">{{ __('auth.login.register_link') }}</a>
    </p>
</div>

<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.login.title') }}</h1>

    <form wire:submit="login" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.login.email') }}</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.login.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="current-password"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4 flex items-center gap-2">
            <input id="remember" type="checkbox" wire:model="remember"
                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="remember" class="text-sm text-gray-700">{{ __('auth.login.remember') }}</label>
        </div>

        <button type="submit"
                class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('auth.login.submit') }}
        </button>
    </form>

    <p class="mt-4 text-sm">
        <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">{{ __('auth.login.forgot') }}</a>
    </p>

    <p class="mt-2 text-sm">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline">{{ __('auth.login.register_link') }}</a>
    </p>
</div>

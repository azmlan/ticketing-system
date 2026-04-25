<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.password_reset.title') }}</h1>

    <form wire:submit="resetPassword" novalidate>
        @csrf

        <input type="hidden" wire:model="token">
        <input type="hidden" wire:model="email">

        <div class="mb-4">
            <label for="email_display" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password_reset.email') }}</label>
            <input id="email_display" type="email" value="{{ $email }}" readonly
                   class="block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 shadow-sm">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password_reset.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="new-password"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password_reset.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('auth.password_reset.submit') }}
        </button>
    </form>
</div>

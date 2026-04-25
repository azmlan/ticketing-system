<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.password_request.title') }}</h1>

    @if($sent)
        <p class="text-green-700">{{ __('auth.password_reset_sent') }}</p>
    @else
        <form wire:submit="sendResetLink" novalidate>
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.password_request.email') }}</label>
                <input id="email" type="email" wire:model="email" required autocomplete="email"
                       class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                {{ __('auth.password_request.submit') }}
            </button>
        </form>
    @endif

    <p class="mt-4 text-sm">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">{{ __('auth.password_request.back_login') }}</a>
    </p>
</div>

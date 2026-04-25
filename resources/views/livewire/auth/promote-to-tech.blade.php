<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('promote.title') }}</h1>

    @if($promoted)
        <p class="mb-4 text-green-700 font-medium">{{ __('promote.success') }}</p>
    @endif

    <form wire:submit="promote" novalidate>
        @csrf

        <div class="mb-4">
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('promote.select_user') }}</label>
            <select id="user_id" wire:model="user_id"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">--</option>
                @foreach($candidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->full_name }} ({{ $candidate->email }})</option>
                @endforeach
            </select>
            @error('user_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('promote.submit') }}
        </button>
    </form>
</div>

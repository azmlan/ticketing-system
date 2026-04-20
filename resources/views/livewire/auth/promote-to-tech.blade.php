<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('promote.title') }}</h1>

    @if($promoted)
        <p class="mb-4 text-green-700">{{ __('promote.success') }}</p>
    @endif

    <form wire:submit="promote" novalidate>
        @csrf

        <div class="mb-4">
            <label for="user_id">{{ __('promote.select_user') }}</label>
            <select id="user_id" wire:model="user_id">
                <option value="">--</option>
                @foreach($candidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->full_name }} ({{ $candidate->email }})</option>
                @endforeach
            </select>
            @error('user_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit">{{ __('promote.submit') }}</button>
    </form>
</div>

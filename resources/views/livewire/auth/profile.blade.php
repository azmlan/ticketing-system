<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('profile.title') }}</h1>

    @if($saved)
        <p class="mb-4 text-green-700">{{ __('profile.saved') }}</p>
    @endif

    {{-- Profile fields --}}
    <form wire:submit="saveProfile" novalidate>
        @csrf

        <div class="mb-4">
            <label for="full_name">{{ __('profile.full_name') }}</label>
            <input id="full_name" type="text" wire:model="full_name" required autocomplete="name">
            @error('full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="email">{{ __('profile.email') }}</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="phone">{{ __('profile.phone') }}</label>
            <input id="phone" type="tel" wire:model="phone" autocomplete="tel">
            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="employee_number">{{ __('profile.employee_number') }}</label>
            <input id="employee_number" type="text" wire:model="employee_number">
            @error('employee_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="department_id">{{ __('profile.department') }}</label>
            <select id="department_id" wire:model="department_id">
                <option value="">--</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ app()->getLocale() === 'ar' ? $dept->name_ar : $dept->name_en }}</option>
                @endforeach
            </select>
            @error('department_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="location_id">{{ __('profile.location') }}</label>
            <select id="location_id" wire:model="location_id">
                <option value="">--</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ app()->getLocale() === 'ar' ? $loc->name_ar : $loc->name_en }}</option>
                @endforeach
            </select>
            @error('location_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="locale">{{ __('profile.language') }}</label>
            <select id="locale" wire:model="locale">
                <option value="ar">العربية</option>
                <option value="en">English</option>
            </select>
            @error('locale') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Current password required if email changed --}}
        <div class="mb-4" x-show="$wire.email !== '{{ auth()->user()->email }}'">
            <label for="current_password_profile">{{ __('profile.current_password') }}</label>
            <input id="current_password_profile" type="password" wire:model="current_password" autocomplete="current-password">
            @error('current_password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit">{{ __('common.save') }}</button>
    </form>

    {{-- Change password section --}}
    <hr class="my-8">

    <h2 class="text-xl font-semibold mb-4">{{ __('profile.change_password') }}</h2>

    <form wire:submit="changePassword" novalidate>
        @csrf

        <div class="mb-4">
            <label for="cp_current">{{ __('profile.current_password') }}</label>
            <input id="cp_current" type="password" wire:model="current_password" autocomplete="current-password">
            @error('current_password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="cp_new">{{ __('profile.new_password') }}</label>
            <input id="cp_new" type="password" wire:model="password" autocomplete="new-password">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="cp_confirm">{{ __('profile.password_confirmation') }}</label>
            <input id="cp_confirm" type="password" wire:model="password_confirmation" autocomplete="new-password">
        </div>

        {{-- Directional chevron: flips in RTL --}}
        <button type="submit" class="inline-flex items-center gap-2">
            {{ __('profile.change_password') }}
            <svg class="w-4 h-4 [dir=rtl]_:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </button>
    </form>
</div>

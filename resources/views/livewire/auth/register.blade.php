<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.register.title') }}</h1>

    <form wire:submit="register" novalidate>
        @csrf

        <div class="mb-4">
            <label for="full_name">{{ __('auth.register.full_name') }}</label>
            <input id="full_name" type="text" wire:model="full_name" required autocomplete="name">
            @error('full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="email">{{ __('auth.register.email') }}</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password">{{ __('auth.register.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="new-password">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation">{{ __('auth.register.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="mb-4">
            <label for="employee_number">{{ __('auth.register.employee_number') }}</label>
            <input id="employee_number" type="text" wire:model="employee_number" autocomplete="off">
            @error('employee_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="department_id">{{ __('auth.register.department') }}</label>
            <select id="department_id" wire:model="department_id">
                <option value="">--</option>
                @foreach(\App\Modules\Shared\Models\Department::where('is_active', true)->whereNull('deleted_at')->get() as $dept)
                    <option value="{{ $dept->id }}">{{ app()->getLocale() === 'ar' ? $dept->name_ar : $dept->name_en }}</option>
                @endforeach
            </select>
            @error('department_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="location_id">{{ __('auth.register.location') }}</label>
            <select id="location_id" wire:model="location_id">
                <option value="">--</option>
                @foreach(\App\Modules\Shared\Models\Location::where('is_active', true)->whereNull('deleted_at')->get() as $loc)
                    <option value="{{ $loc->id }}">{{ app()->getLocale() === 'ar' ? $loc->name_ar : $loc->name_en }}</option>
                @endforeach
            </select>
            @error('location_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="phone">{{ __('auth.register.phone') }}</label>
            <input id="phone" type="tel" wire:model="phone" autocomplete="tel">
            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="locale">{{ __('auth.register.locale') }}</label>
            <select id="locale" wire:model="locale">
                <option value="ar">العربية</option>
                <option value="en">English</option>
            </select>
            @error('locale') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit">{{ __('auth.register.submit') }}</button>
    </form>

    <p class="mt-4 text-sm">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}">{{ __('auth.register.login_link') }}</a>
    </p>
</div>

<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('auth.register.title') }}</h1>

    <form wire:submit="register" novalidate>
        @csrf

        <div class="mb-4">
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.full_name') }}</label>
            <input id="full_name" type="text" wire:model="full_name" required autocomplete="name"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('full_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.email') }}</label>
            <input id="email" type="email" wire:model="email" required autocomplete="email"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.password') }}</label>
            <input id="password" type="password" wire:model="password" required autocomplete="new-password"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="employee_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.employee_number') }}</label>
            <input id="employee_number" type="text" wire:model="employee_number" autocomplete="off"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('employee_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.department') }}</label>
            <select id="department_id" wire:model="department_id"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">--</option>
                @foreach(\App\Modules\Shared\Models\Department::where('is_active', true)->whereNull('deleted_at')->get() as $dept)
                    <option value="{{ $dept->id }}">{{ app()->getLocale() === 'ar' ? $dept->name_ar : $dept->name_en }}</option>
                @endforeach
            </select>
            @error('department_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.location') }}</label>
            <select id="location_id" wire:model="location_id"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">--</option>
                @foreach(\App\Modules\Shared\Models\Location::where('is_active', true)->whereNull('deleted_at')->get() as $loc)
                    <option value="{{ $loc->id }}">{{ app()->getLocale() === 'ar' ? $loc->name_ar : $loc->name_en }}</option>
                @endforeach
            </select>
            @error('location_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.phone') }}</label>
            <input id="phone" type="tel" wire:model="phone" autocomplete="tel"
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="locale" class="block text-sm font-medium text-gray-700 mb-1">{{ __('auth.register.locale') }}</label>
            <select id="locale" wire:model="locale"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="ar">العربية</option>
                <option value="en">English</option>
            </select>
            @error('locale') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('auth.register.submit') }}
        </button>
    </form>

    <p class="mt-4 text-sm">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">{{ __('auth.register.login_link') }}</a>
    </p>
</div>

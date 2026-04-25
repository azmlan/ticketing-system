<div class="max-w-2xl mx-auto space-y-8">

    <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.branding.title') }}</h1>

    @if($saved)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
            {{ __('admin.branding.saved') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        {{-- ── Company Identity ──────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-5">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">
                {{ __('admin.branding.identity_section') }}
            </h2>

            {{-- Company name --}}
            <div>
                <label for="companyName" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.branding.company_name') }}
                </label>
                <input type="text"
                       id="companyName"
                       wire:model="companyName"
                       placeholder="{{ __('admin.branding.company_name_placeholder') }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('companyName') border-red-500 @enderror">
                @error('companyName')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Colors --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="primaryColor" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('admin.branding.primary_color') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="color"
                               wire:model="primaryColor"
                               class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5">
                        <input type="text"
                               wire:model="primaryColor"
                               id="primaryColor"
                               maxlength="7"
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('primaryColor') border-red-500 @enderror">
                    </div>
                    @error('primaryColor')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="secondaryColor" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('admin.branding.secondary_color') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="color"
                               wire:model="secondaryColor"
                               class="h-9 w-12 cursor-pointer rounded border border-gray-300 p-0.5">
                        <input type="text"
                               wire:model="secondaryColor"
                               id="secondaryColor"
                               maxlength="7"
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('secondaryColor') border-red-500 @enderror">
                    </div>
                    @error('secondaryColor')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Logo Upload ───────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">
                {{ __('admin.branding.logo_section') }}
            </h2>

            @php $currentLogo = \App\Modules\Admin\Models\AppSetting::get('logo_path'); @endphp
            @if($currentLogo)
                <div>
                    <p class="text-xs text-gray-500 mb-2">{{ __('admin.branding.current_logo') }}</p>
                    <img src="{{ route('admin.logo') }}" alt="{{ __('admin.branding.current_logo') }}"
                         class="h-16 w-auto rounded border border-gray-200 bg-gray-50 p-1">
                </div>
            @else
                <p class="text-sm text-gray-400 italic">{{ __('admin.branding.no_logo') }}</p>
            @endif

            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.branding.logo') }}
                </label>
                <input type="file"
                       id="logo"
                       wire:model="logo"
                       accept="image/*"
                       class="block w-full text-sm text-gray-600 @error('logo') border border-red-400 rounded @enderror">
                <p class="text-xs text-gray-400 mt-1">{{ __('admin.branding.logo_hint') }}</p>
                @error('logo')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ── Session Configuration ─────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">
                {{ __('admin.branding.session_section') }}
            </h2>

            <div>
                <label for="sessionTimeoutHours" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('admin.branding.session_timeout_hours') }}
                </label>
                <input type="number"
                       id="sessionTimeoutHours"
                       wire:model="sessionTimeoutHours"
                       min="1"
                       max="24"
                       class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('sessionTimeoutHours') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">{{ __('admin.branding.session_timeout_hint') }}</p>
                @error('sessionTimeoutHours')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ── Actions ───────────────────────────────────────────────────── --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                {{ __('admin.branding.save') }}
            </button>
        </div>

    </form>
</div>

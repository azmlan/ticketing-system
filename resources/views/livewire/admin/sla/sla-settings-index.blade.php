<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('admin.sla_settings.title') }}</h1>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Section 1: SLA Targets ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">{{ __('admin.sla_settings.targets_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.sla_settings.targets_hint') }}</p>
        </div>

        <div class="p-6">
            <form wire:submit.prevent="saveTargets">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    {{ __('admin.sla_settings.priority') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('admin.sla_settings.response_target') }}
                                </th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('admin.sla_settings.resolution_target') }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                    {{ __('admin.sla_settings.use_24x7') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($priorities as $priority)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    @php
                                        $badgeClass = match($priority) {
                                            'critical' => 'bg-red-100 text-red-800',
                                            'high'     => 'bg-orange-100 text-orange-800',
                                            'medium'   => 'bg-yellow-100 text-yellow-800',
                                            default    => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                        {{ __('admin.sla_settings.priority_' . $priority) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number"
                                           wire:model="targets.{{ $priority }}.response_target_minutes"
                                           min="1"
                                           class="w-32 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error("targets.$priority.response_target_minutes") border-red-500 @enderror">
                                    @error("targets.$priority.response_target_minutes")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number"
                                           wire:model="targets.{{ $priority }}.resolution_target_minutes"
                                           min="1"
                                           class="w-32 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error("targets.$priority.resolution_target_minutes") border-red-500 @enderror">
                                    @error("targets.$priority.resolution_target_minutes")
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox"
                                           wire:model="targets.{{ $priority }}.use_24x7"
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-gray-500">{{ __('admin.sla_settings.use_24x7_hint') }}</p>

                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('admin.sla_settings.save_targets') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Section 2: Business Hours & Config ─────────────────────────────── --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">{{ __('admin.sla_settings.business_hours_title') }}</h2>
        </div>

        <div class="p-6">
            <form wire:submit.prevent="saveBusinessHours">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                    {{-- Business hours start --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('admin.sla_settings.business_hours_start') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model="businessHoursStart"
                               placeholder="08:00"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('businessHoursStart') border-red-500 @enderror">
                        @error('businessHoursStart') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Business hours end --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('admin.sla_settings.business_hours_end') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               wire:model="businessHoursEnd"
                               placeholder="16:00"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('businessHoursEnd') border-red-500 @enderror">
                        @error('businessHoursEnd') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Working days --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('admin.sla_settings.working_days') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($days as $day)
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="workingDays"
                                       value="{{ $day }}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm text-gray-700">{{ __('admin.sla_settings.day_' . $day) }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('workingDays') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('workingDays.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- SLA warning threshold --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('admin.sla_settings.warning_threshold') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   wire:model="slaWarningThreshold"
                                   min="1"
                                   max="99"
                                   class="w-28 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('slaWarningThreshold') border-red-500 @enderror">
                            <span class="text-sm text-gray-500">%</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('admin.sla_settings.warning_threshold_hint') }}</p>
                        @error('slaWarningThreshold') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                </div>

                <div class="mt-6">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('admin.sla_settings.save_business_hours') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

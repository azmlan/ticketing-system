<div>
    <h2 class="text-xl font-bold mb-4">{{ __('escalation.condition_report.title') }}</h2>

    <form wire:submit="submit" novalidate>
        @csrf

        <div class="mb-4">
            <label for="reportType" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.report_type') }}</label>
            <input id="reportType" type="text" wire:model="reportType" maxlength="255" required
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('reportType') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="locationId" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.location') }}</label>
            <select id="locationId" wire:model="locationId"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('escalation.condition_report.select_location') }}</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->localizedName() }}</option>
                @endforeach
            </select>
            @error('locationId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.report_date') }}</label>
            <p class="text-sm text-gray-600">{{ now()->toDateString() }}</p>
        </div>

        {{-- Current Condition (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="currentCondition" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.current_condition') }}</label>
            <textarea id="currentCondition" wire:model="currentCondition" rows="4" required
                      class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('currentCondition') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Condition Analysis (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="conditionAnalysis" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.condition_analysis') }}</label>
            <textarea id="conditionAnalysis" wire:model="conditionAnalysis" rows="4" required
                      class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('conditionAnalysis') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Required Action (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="requiredAction" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.required_action') }}</label>
            <textarea id="requiredAction" wire:model="requiredAction" rows="4" required
                      class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('requiredAction') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Attachments (up to 5 files, processed server-side) --}}
        <div class="mb-6">
            <label for="attachments" class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.condition_report.attachments') }}</label>
            <input id="attachments" type="file" wire:model="attachments" multiple
                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                   class="block w-full text-sm text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @error('attachments') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            @error('attachments.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('escalation.condition_report.submit') }}
        </button>
    </form>
</div>

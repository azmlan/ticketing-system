<div>
    <h2 class="text-xl font-bold mb-4">{{ __('escalation.condition_report.title') }}</h2>

    <form wire:submit="submit" novalidate>
        @csrf

        {{-- Report Type --}}
        <div class="mb-4">
            <label for="reportType">{{ __('escalation.condition_report.report_type') }}</label>
            <input id="reportType" type="text" wire:model="reportType" maxlength="255" required>
            @error('reportType') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Location --}}
        <div class="mb-4">
            <label for="locationId">{{ __('escalation.condition_report.location') }}</label>
            <select id="locationId" wire:model="locationId">
                <option value="">{{ __('escalation.condition_report.select_location') }}</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->localizedName() }}</option>
                @endforeach
            </select>
            @error('locationId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Report Date (read-only, auto-filled to today) --}}
        <div class="mb-4">
            <label>{{ __('escalation.condition_report.report_date') }}</label>
            <p class="text-sm">{{ now()->toDateString() }}</p>
        </div>

        {{-- Current Condition (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="currentCondition">{{ __('escalation.condition_report.current_condition') }}</label>
            <textarea id="currentCondition" wire:model="currentCondition" rows="4" required></textarea>
            @error('currentCondition') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Condition Analysis (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="conditionAnalysis">{{ __('escalation.condition_report.condition_analysis') }}</label>
            <textarea id="conditionAnalysis" wire:model="conditionAnalysis" rows="4" required></textarea>
            @error('conditionAnalysis') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Required Action (rich text — sanitized server-side) --}}
        <div class="mb-4">
            <label for="requiredAction">{{ __('escalation.condition_report.required_action') }}</label>
            <textarea id="requiredAction" wire:model="requiredAction" rows="4" required></textarea>
            @error('requiredAction') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Attachments (up to 5 files, processed server-side) --}}
        <div class="mb-4">
            <label for="attachments">{{ __('escalation.condition_report.attachments') }}</label>
            <input id="attachments" type="file" wire:model="attachments" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx">
            @error('attachments') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            @error('attachments.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mt-6">
            <button type="submit">
                {{ __('escalation.condition_report.submit') }}
            </button>
        </div>
    </form>
</div>

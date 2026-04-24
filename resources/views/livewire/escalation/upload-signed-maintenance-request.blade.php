<div>
    <h3 class="font-semibold text-lg mb-4">{{ __('escalation.upload_signed.title') }}</h3>

    {{-- Policy notice --}}
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
        <p class="text-sm text-blue-800">{{ __('escalation.upload_signed.policy_notice') }}</p>
    </div>

    {{-- Disclaimer reminder --}}
    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
        <p class="text-sm text-yellow-800">{{ __('escalation.upload_signed.disclaimer_reminder') }}</p>
    </div>

    {{-- Download buttons --}}
    <div class="mb-6">
        <p class="font-medium mb-2">{{ __('escalation.upload_signed.download_title') }}</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('escalation.maintenance-request.download', [$ticketId, 'ar']) }}"
               class="inline-block px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">
                {{ __('escalation.maintenance_request.export_ar') }}
            </a>
            <a href="{{ route('escalation.maintenance-request.download', [$ticketId, 'en']) }}"
               class="inline-block px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">
                {{ __('escalation.maintenance_request.export_en') }}
            </a>
        </div>
    </div>

    {{-- Upload form --}}
    <div class="border-t pt-4">
        <label class="block font-medium mb-1">{{ __('escalation.upload_signed.file_label') }}</label>

        @error('signedFile')
            <p class="text-red-600 text-sm mb-1">{{ $message }}</p>
        @enderror

        <input type="file"
               wire:model="signedFile"
               accept=".pdf,.docx"
               class="block mb-4">

        <button wire:click="upload"
                wire:loading.attr="disabled"
                type="button">
            {{ __('escalation.upload_signed.submit') }}
        </button>
    </div>
</div>

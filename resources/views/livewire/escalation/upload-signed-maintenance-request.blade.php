<div>
    <h3 class="font-semibold text-lg mb-4">{{ __('escalation.upload_signed.title') }}</h3>

    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
        <p class="text-sm text-blue-800">{{ __('escalation.upload_signed.policy_notice') }}</p>
    </div>

    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
        <p class="text-sm text-yellow-800">{{ __('escalation.upload_signed.disclaimer_reminder') }}</p>
    </div>

    <div class="mb-6">
        <p class="font-medium mb-2">{{ __('escalation.upload_signed.download_title') }}</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('escalation.maintenance-request.download', [$ticketId, 'ar']) }}"
               class="inline-block rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                {{ __('escalation.maintenance_request.export_ar') }}
            </a>
            <a href="{{ route('escalation.maintenance-request.download', [$ticketId, 'en']) }}"
               class="inline-block rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                {{ __('escalation.maintenance_request.export_en') }}
            </a>
        </div>
    </div>

    <div class="border-t pt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('escalation.upload_signed.file_label') }}</label>

        @error('signedFile')
            <p class="text-red-600 text-sm mb-1">{{ $message }}</p>
        @enderror

        <input type="file"
               wire:model="signedFile"
               accept=".pdf,.docx"
               class="block w-full mb-4 text-sm text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

        <button wire:click="upload"
                wire:loading.attr="disabled"
                type="button"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('escalation.upload_signed.submit') }}
        </button>
    </div>
</div>

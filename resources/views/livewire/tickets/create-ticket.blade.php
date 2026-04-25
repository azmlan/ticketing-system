<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('tickets.create.title') }}</h1>

    <form wire:submit="submit" novalidate>
        @csrf

        <div class="mb-4">
            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.subject') }}</label>
            <input id="subject" type="text" wire:model="subject" maxlength="255" required
                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            @error('subject') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Description (rich text — sanitized server-side before storage) --}}
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.description') }}</label>
            <textarea id="description" wire:model="description" rows="6" required
                      class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
            @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.category') }}</label>
            <select id="category_id" wire:model.live="category_id" required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('tickets.select_category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->localizedName() }}</option>
                @endforeach
            </select>
            @error('category_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        @if ($subcategories->isNotEmpty())
            <div class="mb-4">
                <label for="subcategory_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.subcategory') }}</label>
                <select id="subcategory_id" wire:model="subcategory_id"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('tickets.select_subcategory') }}</option>
                    @foreach ($subcategories as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->localizedName() }}</option>
                    @endforeach
                </select>
                @error('subcategory_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- Attachments (up to 5 files, processed server-side) --}}
        <div class="mb-6">
            <label for="attachments" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.attachments') }}</label>
            <input id="attachments" type="file" wire:model="attachments" multiple
                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx"
                   class="block w-full text-sm text-gray-500 file:me-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @error('attachments') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            @error('attachments.*') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('tickets.create.submit') }}
        </button>
    </form>
</div>

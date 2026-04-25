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

        {{-- Department --}}
        @if ($departments->isNotEmpty())
        <div class="mb-4">
            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.department') }}</label>
            <select id="department_id" wire:model="department_id"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('tickets.select_department') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->localizedName() }}</option>
                @endforeach
            </select>
            @error('department_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        @endif

        {{-- Location --}}
        @if ($locations->isNotEmpty())
        <div class="mb-4">
            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('tickets.create.location') }}</label>
            <select id="location_id" wire:model="location_id"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('tickets.select_location') }}</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->localizedName() }}</option>
                @endforeach
            </select>
            @error('location_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        @endif

        {{-- Custom Fields --}}
        @if ($customFields->isNotEmpty())
            <div class="mb-4 space-y-4">
                @foreach ($customFields as $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field->localizedName() }}
                            @if ($field->is_required)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if ($field->field_type === 'text')
                            <input type="text"
                                   wire:model="customFieldValues.{{ $field->id }}"
                                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">

                        @elseif ($field->field_type === 'number')
                            <input type="number"
                                   wire:model="customFieldValues.{{ $field->id }}"
                                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">

                        @elseif ($field->field_type === 'date')
                            <input type="date"
                                   wire:model="customFieldValues.{{ $field->id }}"
                                   class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">

                        @elseif ($field->field_type === 'checkbox')
                            <div class="flex items-center gap-2">
                                <input type="checkbox"
                                       id="cf_{{ $field->id }}"
                                       wire:model="customFieldValues.{{ $field->id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="cf_{{ $field->id }}" class="text-sm text-gray-700">
                                    {{ $field->localizedName() }}
                                </label>
                            </div>

                        @elseif ($field->field_type === 'dropdown')
                            <select wire:model="customFieldValues.{{ $field->id }}"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">{{ __('tickets.custom_fields.select_option') }}</option>
                                @foreach ($field->options as $option)
                                    <option value="{{ $option->id }}">{{ $option->localizedValue() }}</option>
                                @endforeach
                            </select>

                        @elseif ($field->field_type === 'multi_select')
                            <div class="space-y-1">
                                @foreach ($field->options as $option)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox"
                                               wire:model="customFieldValues.{{ $field->id }}"
                                               value="{{ $option->id }}"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">{{ $option->localizedValue() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @error('customFieldValues.' . $field->id)
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                        @error('customFieldValues.' . $field->id . '.*')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach
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

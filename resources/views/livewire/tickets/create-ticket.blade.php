<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('tickets.create.title') }}</h1>

    <form wire:submit="submit" novalidate>
        @csrf

        {{-- Subject --}}
        <div class="mb-4">
            <label for="subject">{{ __('tickets.create.subject') }}</label>
            <input id="subject" type="text" wire:model="subject" maxlength="255" required>
            @error('subject') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Description (rich text — sanitized server-side before storage) --}}
        <div class="mb-4">
            <label for="description">{{ __('tickets.create.description') }}</label>
            <textarea id="description" wire:model="description" rows="6" required></textarea>
            @error('description') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Category --}}
        <div class="mb-4">
            <label for="category_id">{{ __('tickets.create.category') }}</label>
            <select id="category_id" wire:model.live="category_id" required>
                <option value="">{{ __('tickets.select_category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->localizedName() }}</option>
                @endforeach
            </select>
            @error('category_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        {{-- Subcategory (conditional) --}}
        @if ($subcategories->isNotEmpty())
            <div class="mb-4">
                <label for="subcategory_id">{{ __('tickets.create.subcategory') }}</label>
                <select id="subcategory_id" wire:model="subcategory_id">
                    <option value="">{{ __('tickets.select_subcategory') }}</option>
                    @foreach ($subcategories as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->localizedName() }}</option>
                    @endforeach
                </select>
                @error('subcategory_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- Attachments hook (implemented in Task 2.5) --}}
        {{-- wire:model="attachments" placeholder --}}

        <button type="submit">{{ __('tickets.create.submit') }}</button>
    </form>
</div>

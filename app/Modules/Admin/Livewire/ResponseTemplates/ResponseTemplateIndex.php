<?php

namespace App\Modules\Admin\Livewire\ResponseTemplates;

use App\Modules\Communication\Models\ResponseTemplate;
use App\Modules\Tickets\Services\RichTextSanitizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ResponseTemplateIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $filterInternal = ''; // '' | '1' | '0'

    public ?string $showFormFor = null; // 'create' or template ULID

    public string $formTitleAr = '';

    public string $formTitleEn = '';

    public string $formBodyAr = '';

    public string $formBodyEn = '';

    public bool $formIsInternal = true;

    public bool $formIsActive = true;

    protected function rules(): array
    {
        return [
            'formTitleAr'    => ['required', 'string', 'max:255'],
            'formTitleEn'    => ['required', 'string', 'max:255'],
            'formBodyAr'     => ['required', 'string'],
            'formBodyEn'     => ['required', 'string'],
            'formIsInternal' => ['boolean'],
            'formIsActive'   => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formTitleAr.required' => __('validation.required', ['attribute' => __('admin.response_templates.title_ar')]),
            'formTitleEn.required' => __('validation.required', ['attribute' => __('admin.response_templates.title_en')]),
            'formBodyAr.required'  => __('validation.required', ['attribute' => __('admin.response_templates.body_ar')]),
            'formBodyEn.required'  => __('validation.required', ['attribute' => __('admin.response_templates.body_en')]),
        ];
    }

    public function mount(): void
    {
        $this->authorize('system.manage-response-templates');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterInternal(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('system.manage-response-templates');
        $this->resetForm();
        $this->showFormFor = 'create';
    }

    public function openEdit(string $id): void
    {
        $this->authorize('system.manage-response-templates');
        $template = ResponseTemplate::findOrFail($id);
        $this->formTitleAr    = $template->title_ar;
        $this->formTitleEn    = $template->title_en;
        $this->formBodyAr     = $template->body_ar;
        $this->formBodyEn     = $template->body_en;
        $this->formIsInternal = $template->is_internal;
        $this->formIsActive   = $template->is_active;
        $this->showFormFor    = $id;
    }

    public function save(): void
    {
        $this->authorize('system.manage-response-templates');
        $this->validate();

        $sanitizer = app(RichTextSanitizer::class);
        $bodyAr    = $sanitizer->sanitize($this->formBodyAr);
        $bodyEn    = $sanitizer->sanitize($this->formBodyEn);

        if ($this->showFormFor === 'create') {
            ResponseTemplate::create([
                'title_ar'    => $this->formTitleAr,
                'title_en'    => $this->formTitleEn,
                'body_ar'     => $bodyAr,
                'body_en'     => $bodyEn,
                'is_internal' => $this->formIsInternal,
                'is_active'   => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.response_templates.created'));
        } else {
            $template = ResponseTemplate::findOrFail($this->showFormFor);
            $template->update([
                'title_ar'    => $this->formTitleAr,
                'title_en'    => $this->formTitleEn,
                'body_ar'     => $bodyAr,
                'body_en'     => $bodyEn,
                'is_internal' => $this->formIsInternal,
                'is_active'   => $this->formIsActive,
            ]);
            session()->flash('success', __('admin.response_templates.updated'));
        }

        $this->resetForm();
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('system.manage-response-templates');
        $template = ResponseTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        $msg = $template->is_active
            ? __('admin.response_templates.activated')
            : __('admin.response_templates.deactivated');
        session()->flash('success', $msg);
    }

    public function delete(string $id): void
    {
        $this->authorize('system.manage-response-templates');
        ResponseTemplate::findOrFail($id)->delete();
        session()->flash('success', __('admin.response_templates.deleted'));
        $this->resetPage();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showFormFor    = null;
        $this->formTitleAr    = '';
        $this->formTitleEn    = '';
        $this->formBodyAr     = '';
        $this->formBodyEn     = '';
        $this->formIsInternal = true;
        $this->formIsActive   = true;
        $this->resetValidation();
    }

    public function render()
    {
        $templates = ResponseTemplate::withTrashed()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title_ar', 'like', '%'.$this->search.'%')
                    ->orWhere('title_en', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filterInternal !== '', fn ($q) => $q->where('is_internal', (bool) $this->filterInternal))
            ->orderBy('title_en')
            ->paginate(15);

        return view('livewire.admin.response-templates.response-template-index', compact('templates'));
    }
}

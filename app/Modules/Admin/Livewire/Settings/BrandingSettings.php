<?php

namespace App\Modules\Admin\Livewire\Settings;

use App\Modules\Admin\Models\AppSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class BrandingSettings extends Component
{
    use WithFileUploads;

    public string $companyName = '';

    public string $primaryColor = '';

    public string $secondaryColor = '';

    public int|string $sessionTimeoutHours = 8;

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public bool $saved = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->is_super_user, 403);

        $this->companyName         = (string) (AppSetting::get('company_name') ?? '');
        $this->primaryColor        = (string) (AppSetting::get('primary_color') ?? '#4f46e5');
        $this->secondaryColor      = (string) (AppSetting::get('secondary_color') ?? '#7c3aed');
        $this->sessionTimeoutHours = (int) (AppSetting::get('session_timeout_hours') ?? 8);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->is_super_user, 403);

        $this->validate([
            'companyName'         => 'required|string|max:255',
            'primaryColor'        => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondaryColor'      => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sessionTimeoutHours' => 'required|integer|min:1|max:24',
            'logo'                => 'nullable|max:2048',
        ]);

        AppSetting::set('company_name',          $this->companyName);
        AppSetting::set('primary_color',         $this->primaryColor);
        AppSetting::set('secondary_color',       $this->secondaryColor);
        AppSetting::set('session_timeout_hours', (string) $this->sessionTimeoutHours);

        if ($this->logo) {
            $this->storeLogo();
            return;
        }

        $this->saved = true;
    }

    private function storeLogo(): void
    {
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($this->logo->getPathname());

        if (! str_starts_with($mimeType, 'image/')) {
            $this->addError('logo', __('admin.branding.logo_invalid_mime'));
            return;
        }

        $ulid = (string) Str::ulid();
        $path = "logos/{$ulid}";

        $image   = Image::decodePath($this->logo->getPathname());
        $image->scaleDown(width: 256, height: 256);
        $encoded = $image->encode(new JpegEncoder(quality: 80));
        Storage::disk('local')->put($path, (string) $encoded);

        $oldPath = AppSetting::get('logo_path');
        if ($oldPath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        AppSetting::set('logo_path', $path);
        $this->logo = null;
        $this->saved = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.branding-settings');
    }
}

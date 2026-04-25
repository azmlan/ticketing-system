<?php

namespace App\Modules\Admin\Livewire\Sla;

use App\Modules\Admin\Models\AppSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class SlaSettingsIndex extends Component
{
    use AuthorizesRequests;

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    public const DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    public array  $targets             = [];
    public string $businessHoursStart  = '08:00';
    public string $businessHoursEnd    = '16:00';
    public array  $workingDays         = [];
    public int    $slaWarningThreshold = 75;

    public function mount(): void
    {
        $this->authorize('system.manage-sla');
        $this->loadTargets();
        $this->loadBusinessHours();
    }

    private function loadTargets(): void
    {
        $policies = DB::table('sla_policies')->get()->keyBy('priority');

        foreach (self::PRIORITIES as $priority) {
            $policy = $policies->get($priority);
            $this->targets[$priority] = $policy ? [
                'response_target_minutes'   => $policy->response_target_minutes,
                'resolution_target_minutes' => $policy->resolution_target_minutes,
                'use_24x7'                  => (bool) $policy->use_24x7,
            ] : [
                'response_target_minutes'   => 60,
                'resolution_target_minutes' => 480,
                'use_24x7'                  => false,
            ];
        }
    }

    private function loadBusinessHours(): void
    {
        $this->businessHoursStart    = AppSetting::get('business_hours_start', '08:00');
        $this->businessHoursEnd      = AppSetting::get('business_hours_end', '16:00');
        $workingDays                 = AppSetting::get('working_days');
        $this->workingDays           = $workingDays !== null
            ? json_decode($workingDays, true)
            : ['sun', 'mon', 'tue', 'wed', 'thu'];
        $this->slaWarningThreshold   = (int) AppSetting::get('sla_warning_threshold', 75);
    }

    // ── Save SLA targets ──────────────────────────────────────────────────────

    public function saveTargets(): void
    {
        $this->authorize('system.manage-sla');
        $this->validate($this->targetRules(), $this->targetMessages());

        foreach (self::PRIORITIES as $priority) {
            DB::table('sla_policies')
                ->where('priority', $priority)
                ->update([
                    'response_target_minutes'   => (int) $this->targets[$priority]['response_target_minutes'],
                    'resolution_target_minutes' => (int) $this->targets[$priority]['resolution_target_minutes'],
                    'use_24x7'                  => (bool) $this->targets[$priority]['use_24x7'],
                    'updated_at'                => now(),
                ]);
        }

        session()->flash('success', __('admin.sla_settings.targets_saved'));
    }

    // ── Save business hours config ────────────────────────────────────────────

    public function saveBusinessHours(): void
    {
        $this->authorize('system.manage-sla');
        $this->validate($this->businessHoursRules(), $this->businessHoursMessages());

        AppSetting::set('business_hours_start', $this->businessHoursStart);
        AppSetting::set('business_hours_end', $this->businessHoursEnd);
        AppSetting::set('working_days', json_encode(array_values($this->workingDays)));
        AppSetting::set('sla_warning_threshold', (string) $this->slaWarningThreshold);

        session()->flash('success', __('admin.sla_settings.business_hours_saved'));
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function targetRules(): array
    {
        $rules = [];
        foreach (self::PRIORITIES as $priority) {
            $rules["targets.$priority.response_target_minutes"]   = ['required', 'integer', 'min:1'];
            $rules["targets.$priority.resolution_target_minutes"] = ['required', 'integer', 'min:1'];
            $rules["targets.$priority.use_24x7"]                  = ['boolean'];
        }
        return $rules;
    }

    private function targetMessages(): array
    {
        return [
            'targets.*.response_target_minutes.required'   => __('validation.required', ['attribute' => __('admin.sla_settings.response_target')]),
            'targets.*.response_target_minutes.integer'    => __('validation.integer', ['attribute' => __('admin.sla_settings.response_target')]),
            'targets.*.response_target_minutes.min'        => __('validation.min.numeric', ['attribute' => __('admin.sla_settings.response_target'), 'min' => 1]),
            'targets.*.resolution_target_minutes.required' => __('validation.required', ['attribute' => __('admin.sla_settings.resolution_target')]),
            'targets.*.resolution_target_minutes.integer'  => __('validation.integer', ['attribute' => __('admin.sla_settings.resolution_target')]),
            'targets.*.resolution_target_minutes.min'      => __('validation.min.numeric', ['attribute' => __('admin.sla_settings.resolution_target'), 'min' => 1]),
        ];
    }

    private function businessHoursRules(): array
    {
        return [
            'businessHoursStart'  => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'businessHoursEnd'    => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'workingDays'         => ['required', 'array', 'min:1'],
            'workingDays.*'       => ['in:sun,mon,tue,wed,thu,fri,sat'],
            'slaWarningThreshold' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    private function businessHoursMessages(): array
    {
        return [
            'businessHoursStart.regex'   => __('admin.sla_settings.invalid_time'),
            'businessHoursEnd.regex'     => __('admin.sla_settings.invalid_time'),
            'workingDays.required'       => __('admin.sla_settings.working_days_required'),
            'workingDays.min'            => __('admin.sla_settings.working_days_required'),
            'workingDays.*.in'           => __('admin.sla_settings.invalid_day'),
            'slaWarningThreshold.min'    => __('validation.min.numeric', ['attribute' => __('admin.sla_settings.warning_threshold'), 'min' => 1]),
            'slaWarningThreshold.max'    => __('validation.max.numeric', ['attribute' => __('admin.sla_settings.warning_threshold'), 'max' => 99]),
        ];
    }

    public function render()
    {
        return view('livewire.admin.sla.sla-settings-index', [
            'priorities' => self::PRIORITIES,
            'days'       => self::DAYS,
        ]);
    }
}

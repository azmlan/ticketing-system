<?php

namespace App\Modules\Auth\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Profile extends Component
{
    public string $full_name        = '';
    public string $email            = '';
    public string $phone            = '';
    public string $employee_number  = '';
    public string $department_id    = '';
    public string $location_id      = '';
    public string $locale           = 'ar';

    public string $current_password      = '';
    public string $password              = '';
    public string $password_confirmation = '';

    public bool $saved = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->full_name       = $user->full_name;
        $this->email           = $user->email;
        $this->phone           = $user->phone ?? '';
        $this->employee_number = $user->employee_number ?? '';
        $this->department_id   = $user->department_id ?? '';
        $this->location_id     = $user->location_id   ?? '';
        $this->locale          = $user->locale;
    }

    public function saveProfile(): void
    {
        $user = Auth::user();

        $emailChanged = $this->email !== $user->email;

        $rules = [
            'full_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'           => ['nullable', 'string', 'max:20'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'location_id'     => ['nullable', 'exists:locations,id'],
            'locale'          => ['required', 'in:ar,en'],
        ];

        if ($emailChanged) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $this->validate($rules);

        $user->forceFill([
            'full_name'       => $validated['full_name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'] ?: null,
            'employee_number' => $validated['employee_number'] ?: null,
            'department_id'   => $validated['department_id'] ?: null,
            'location_id'     => $validated['location_id'] ?: null,
            'locale'          => $validated['locale'],
        ])->save();

        $this->saved = true;
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        Auth::user()->forceFill(['password' => $this->password])->save();

        $this->current_password      = '';
        $this->password              = '';
        $this->password_confirmation = '';

        $this->saved = true;
    }

    public function render()
    {
        $departments = \App\Modules\Shared\Models\Department::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        $locations = \App\Modules\Shared\Models\Location::where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        return view('livewire.auth.profile', compact('departments', 'locations'));
    }
}

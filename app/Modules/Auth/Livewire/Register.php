<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Register extends Component
{
    public string $full_name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $employee_number = '';
    public string $department_id = '';
    public string $location_id = '';
    public string $phone = '';
    public string $locale = 'ar';

    public function register(): void
    {
        $key = 'register:ip:' . request()->ip();
        $max = config('rate_limits.register.max_attempts', 3);
        $decay = config('rate_limits.register.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429);
        }

        $validated = $this->validate([
            'full_name'             => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()->symbols()],
            'employee_number'       => ['nullable', 'string', 'max:50'],
            'department_id'         => ['nullable', 'exists:departments,id'],
            'location_id'           => ['nullable', 'exists:locations,id'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'locale'                => ['required', 'in:ar,en'],
        ]);

        RateLimiter::hit($key, $decay);

        $provider = app(AuthProviderInterface::class);

        $provider->register([
            'full_name'       => $validated['full_name'],
            'email'           => $validated['email'],
            'password'        => $validated['password'],
            'employee_number' => $validated['employee_number'] ?? null,
            'department_id'   => $validated['department_id'] ?? null,
            'location_id'     => $validated['location_id'] ?? null,
            'phone'           => $validated['phone'] ?? null,
            'locale'          => $validated['locale'],
        ]);

        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

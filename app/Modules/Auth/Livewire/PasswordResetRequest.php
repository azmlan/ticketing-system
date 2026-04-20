<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class PasswordResetRequest extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendResetLink(): void
    {
        $key   = 'reset:email:' . strtolower($this->email);
        $max   = config('rate_limits.password_reset.max_attempts', 3);
        $decay = config('rate_limits.password_reset.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429);
        }

        $this->validate([
            'email' => ['required', 'email'],
        ]);

        RateLimiter::hit($key, $decay);

        $provider = app(AuthProviderInterface::class);
        $provider->sendPasswordReset($this->email);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.password-reset-request');
    }
}

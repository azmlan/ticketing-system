<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $ipKey    = 'login:ip:' . request()->ip();
        $emailKey = 'login:email:' . strtolower($this->email);
        $max      = config('rate_limits.login.max_attempts', 5);
        $decay    = config('rate_limits.login.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($ipKey, $max) || RateLimiter::tooManyAttempts($emailKey, $max)) {
            abort(429);
        }

        $this->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $provider = app(AuthProviderInterface::class);

        if (! $provider->attempt($this->email, $this->password)) {
            RateLimiter::hit($ipKey, $decay);
            RateLimiter::hit($emailKey, $decay);

            $this->addError('email', __('auth.failed'));

            return;
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($emailKey);

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class PasswordReset extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $provider = app(AuthProviderInterface::class);

        $success = $provider->resetPassword($this->email, $this->token, $this->password);

        if (! $success) {
            $this->addError('email', __('auth.password_reset_invalid'));
            return;
        }

        $this->redirect(route('login') . '?reset=1', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.password-reset');
    }
}

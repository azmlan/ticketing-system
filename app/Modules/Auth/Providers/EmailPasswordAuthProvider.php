<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use App\Modules\Shared\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class EmailPasswordAuthProvider implements AuthProviderInterface
{
    public function register(array $data): User
    {
        return User::create([
            'full_name'       => $data['full_name'],
            'email'           => $data['email'],
            'password'        => $data['password'],
            'employee_number' => $data['employee_number'] ?: null,
            'department_id'   => $data['department_id'] ?: null,
            'location_id'     => $data['location_id'] ?: null,
            'phone'           => $data['phone'] ?: null,
            'locale'          => $data['locale'] ?? 'ar',
            'is_tech'         => false,
            'is_super_user'   => false,
        ]);
    }

    public function attempt(string $email, string $password): bool
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            return false;
        }

        session()->regenerate();

        return true;
    }

    public function logout(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    public function sendPasswordReset(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $status = Password::reset(
            [
                'email'                 => $email,
                'token'                 => $token,
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET;
    }
}

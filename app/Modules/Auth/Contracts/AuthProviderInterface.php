<?php

namespace App\Modules\Auth\Contracts;

use App\Modules\Shared\Models\User;

interface AuthProviderInterface
{
    public function register(array $data): User;

    public function attempt(string $email, string $password): bool;

    public function logout(): void;

    public function sendPasswordReset(string $email): void;

    public function resetPassword(string $email, string $token, string $newPassword): bool;
}

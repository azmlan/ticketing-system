<?php

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Contracts\AuthProviderInterface;
use App\Modules\Auth\Livewire\Login;
use App\Modules\Auth\Livewire\PasswordReset;
use App\Modules\Auth\Livewire\PasswordResetRequest;
use App\Modules\Auth\Livewire\Profile;
use App\Modules\Auth\Livewire\PromoteToTech;
use App\Modules\Auth\Livewire\Register;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthProviderInterface::class, EmailPasswordAuthProvider::class);
    }

    public function boot(): void
    {
        Livewire::component('auth.register', Register::class);
        Livewire::component('auth.login', Login::class);
        Livewire::component('auth.password-reset-request', PasswordResetRequest::class);
        Livewire::component('auth.password-reset', PasswordReset::class);
        Livewire::component('auth.profile', Profile::class);
        Livewire::component('auth.promote-to-tech', PromoteToTech::class);

        Route::middleware('web')->group(__DIR__ . '/../Routes/web.php');
    }
}

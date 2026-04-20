<?php

use App\Modules\Auth\Livewire\Login;
use App\Modules\Auth\Livewire\PasswordReset;
use App\Modules\Auth\Livewire\PasswordResetRequest;
use App\Modules\Auth\Livewire\Profile;
use App\Modules\Auth\Livewire\Register;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/password/reset', PasswordResetRequest::class)->name('password.request');
    Route::get('/password/reset/{token}', PasswordReset::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', Profile::class)->name('profile');

    Route::post('/logout', function () {
        app(\App\Modules\Auth\Contracts\AuthProviderInterface::class)->logout();
        return redirect()->route('login');
    })->name('logout');
});

Route::get('/home', fn () => view('welcome'))->name('home');

<?php

use App\Modules\Auth\Contracts\AuthProviderInterface;
use App\Modules\Auth\Providers\EmailPasswordAuthProvider;

test('container resolves AuthProviderInterface to EmailPasswordAuthProvider', function () {
    $resolved = app(AuthProviderInterface::class);

    expect($resolved)->toBeInstanceOf(EmailPasswordAuthProvider::class);
});

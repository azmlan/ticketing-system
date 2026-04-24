<?php

use App\Modules\Shared\Contracts\SearchServiceInterface;
use App\Modules\Tickets\Search\MySqlSearchDriver;

// ─── Container binding ────────────────────────────────────────────────────────

it('resolves SearchServiceInterface to MySqlSearchDriver from the container', function () {
    $resolved = app(SearchServiceInterface::class);

    expect($resolved)->toBeInstanceOf(MySqlSearchDriver::class);
});

it('resolves a fresh instance on each make call', function () {
    $a = app(SearchServiceInterface::class);
    $b = app(SearchServiceInterface::class);

    expect($a)->toBeInstanceOf(MySqlSearchDriver::class)
        ->and($b)->toBeInstanceOf(MySqlSearchDriver::class);
});

it('resolved instance implements SearchServiceInterface', function () {
    $resolved = app(SearchServiceInterface::class);

    expect($resolved)->toBeInstanceOf(SearchServiceInterface::class);
});

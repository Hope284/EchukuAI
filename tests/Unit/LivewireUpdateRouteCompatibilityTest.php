<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('keeps the default Livewire update route name required by cached public and dashboard views', function () {
    $route = Route::getRoutes()->getByName('default.livewire.update');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('livewire/update')
        ->and($route->methods())->toContain('POST');
});

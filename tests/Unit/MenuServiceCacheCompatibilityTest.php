<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\SettingTwo;
use App\Services\Common\MenuService;

beforeEach(function () {
    Setting::factory()->create();
    SettingTwo::factory()->create();
});

it('merges legacy array-shaped menu cache entries safely', function () {
    $items = [[
        'id'        => 1,
        'key'       => 'dashboard',
        'parent_id' => null,
        'is_active' => true,
        'children'  => [],
    ]];

    $menu = app(MenuService::class)->merge($items);

    expect($menu)->toHaveKey('dashboard')
        ->and($menu['dashboard']['route'])->toBe('dashboard.user.index')
        ->and($menu['dashboard']['children'])->toBe([]);
});

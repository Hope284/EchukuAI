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
        ->and($menu['dashboard']['children_count'])->toBe(0)
        ->and($menu['dashboard']['children'])->toBe([]);
});

it('preserves computed child counts required by dashboard navigation views', function () {
    $items = [[
        'id'             => 1,
        'key'            => 'settings',
        'parent_id'      => null,
        'is_active'      => true,
        'children_count' => 1,
        'children'       => [[
            'id'        => 2,
            'key'       => 'general_settings',
            'parent_id' => 1,
            'is_active' => true,
        ]],
    ]];

    $menu = app(MenuService::class)->merge($items);

    expect($menu['settings']['children_count'])->toBe(1)
        ->and($menu['settings']['children'])->toHaveKey('general_settings')
        ->and($menu['settings']['children']['general_settings']['children_count'])->toBe(0);
});

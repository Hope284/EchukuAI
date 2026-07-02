<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::factory()->create(['script_version' => '10.90']);
});

it('redirects guests away from the update manual', function () {
    $this->get('/update-manual')->assertRedirect('/login');
});

it('blocks normal users from the update manual', function () {
    $user = User::factory()->create(['type' => Roles::USER->value]);

    $this->actingAs($user)->get('/update-manual')->assertForbidden();
});

it('shows the update manual to super administrators without running an update', function () {
    $admin = User::factory()->create(['type' => Roles::SUPER_ADMIN->value]);

    $this->actingAs($admin)
        ->get('/update-manual')
        ->assertOk()
        ->assertSee('DZEVA Update Status')
        ->assertSee('10.9.0');
});

it('shows the public version label only to regular users', function () {
    $user = User::factory()->make(['type' => Roles::USER->value]);
    $admin = User::factory()->make(['type' => Roles::SUPER_ADMIN->value]);

    expect(dzeva_version_label($user))->toBe('DZEVA Version 1.2')
        ->and(dzeva_version_label($admin))->toContain('10.9.0');
});

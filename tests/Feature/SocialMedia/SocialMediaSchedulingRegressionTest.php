<?php

declare(strict_types=1);

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;

it('treats connected platforms with no expiry as active and excludes expired accounts', function () {
    $user = User::factory()->create();

    $active = SocialMediaPlatform::query()->create([
        'user_id'      => $user->id,
        'platform'     => 'linkedin',
        'credentials'  => ['access_token' => 'test-token'],
        'connected_at' => now(),
        'expires_at'   => null,
    ]);
    SocialMediaPlatform::query()->create([
        'user_id'      => $user->id,
        'platform'     => 'facebook',
        'credentials'  => ['access_token' => 'expired-token'],
        'connected_at' => now()->subMonth(),
        'expires_at'   => now()->subDay(),
    ]);

    expect(SocialMediaPlatform::query()->where('user_id', $user->id)->connected()->pluck('id')->all())
        ->toBe([$active->id]);
});

it('registers valid Artisan scheduler command names with overlap protection', function () {
    $provider = file_get_contents(app_path('Extensions/SocialMedia/System/SocialMediaServiceProvider.php'));

    expect($provider)->toContain("command('app:social-media-published-command')->everyTwoMinutes()->withoutOverlapping")
        ->and($provider)->toContain("command('app:social-media-sync-followers')->hourly()->withoutOverlapping")
        ->and($provider)->not->toContain("command('php artisan app:social-media-sync-followers')");
});

it('atomically claims scheduled posts before calling a provider', function () {
    $command = file_get_contents(app_path('Extensions/SocialMedia/System/Console/Commands/PublishedCommand.php'));

    expect($command)->toContain("where('status', StatusEnum::scheduled->value)")
        ->and($command)->toContain("update(['status' => StatusEnum::pending->value])")
        ->and($command)->toContain('if ($claimed !== 1)');
});

<?php

declare(strict_types=1);

use App\Domains\Marketplace\MarketplaceServiceProvider;
use App\Extensions\AIChatPro\System\Connectors\ConnectorPlanGate;
use App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector;
use App\Extensions\SocialMediaAgent\System\Models\SocialMediaAgent;
use App\Extensions\SocialMediaAgent\System\Notifications\PostGenerationCompletedNotification;
use App\Models\Plan;
use App\Support\Security\SafeRemoteUrl;
use Illuminate\Support\Facades\Crypt;

it('registers the required 10.9 extensions at their expected versions', function () {
    $extensions = [
        'PhoneCallAgent'            => ['Phone Call Agent', '1.0', 'phone-call-agent'],
        'AIChatProGmail'            => ['AI Chat Pro Gmail Connector', '1.0', 'ai-chat-pro-gmail'],
        'AIChatProGoogleCalendar'   => ['AI Chat Pro Google Calendar Connector', '1.0', 'ai-chat-pro-google-calendar'],
        'AIChatProGoogleDrive'      => ['AI Chat Pro Google Drive Connector', '1.0', 'ai-chat-pro-google-drive'],
        'AIChatProNotion'           => ['AI Chat Pro Notion Connector', '1.0', 'ai-chat-pro-notion'],
        'AIChatProOutlook'          => ['AI Chat Pro Outlook Connector', '1.0', 'ai-chat-pro-outlook'],
        'AIChatPro'                 => ['AIChatPro', '3.7', 'ai-chat-pro'],
        'AIAgent'                   => ['AI Agent', '1.1', 'ai-agent'],
    ];

    $providers = MarketplaceServiceProvider::getExtensionProviders();

    foreach ($extensions as $directory => [$name, $version, $providerKey]) {
        $manifest = json_decode(
            file_get_contents(app_path("Extensions/{$directory}/extension.json")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($manifest['name'])->toBe($name)
            ->and((string) $manifest['version'])->toBe($version)
            ->and($providers)->toHaveKey($providerKey)
            ->and(class_exists($providers[$providerKey]))->toBeTrue();
    }
});

it('denies connectors without a plan and honors configured plan permissions', function () {
    $allowed = new Plan(['ai_chat_pro_connectors' => [
        'gmail' => true,
        'notion' => false,
    ]]);

    expect(ConnectorPlanGate::allowsForPlan(null, 'gmail'))->toBeFalse()
        ->and(ConnectorPlanGate::allowsForPlan($allowed, 'gmail'))->toBeTrue()
        ->and(ConnectorPlanGate::allowsForPlan($allowed, 'notion'))->toBeFalse();
});

it('encrypts connector access and refresh tokens and hides credentials', function () {
    $connector = new AIChatProConnector();
    $connector->setCredentials([
        'access_token'  => 'access-secret',
        'refresh_token' => 'refresh-secret',
        'account_name'  => 'DZEVA Test',
    ]);

    $stored = $connector->credentials;

    expect($stored['access_token'])->not->toBe('access-secret')
        ->and($stored['refresh_token'])->not->toBe('refresh-secret')
        ->and(Crypt::decryptString($stored['access_token']))->toBe('access-secret')
        ->and(Crypt::decryptString($stored['refresh_token']))->toBe('refresh-secret')
        ->and($connector->toArray())->not->toHaveKey('credentials');
});

it('does not serialize phone agent provider models or booking secrets', function () {
    $resource = file_get_contents(app_path('Extensions/PhoneCallAgent/System/Http/Resources/PhoneCallAgentResource.php'));

    expect($resource)->not->toContain("'booking_api_key'")
        ->and($resource)->not->toContain("'ai_model'")
        ->and($resource)->not->toContain("'provider'               =>")
        ->and($resource)->toContain("'service_type'");
});

it('rejects unsafe phone agent training URLs', function () {
    expect(SafeRemoteUrl::isAllowed('javascript:alert(1)'))->toBeFalse()
        ->and(SafeRemoteUrl::isAllowed('http://127.0.0.1/private'))->toBeFalse()
        ->and(SafeRemoteUrl::isAllowed('http://10.0.0.1/private'))->toBeFalse()
        ->and(SafeRemoteUrl::isAllowed('http://user:pass@example.com'))->toBeFalse()
        ->and(SafeRemoteUrl::isAllowed('https://example.com:8443'))->toBeFalse();
});

it('keeps connector client secrets out of admin html', function () {
    $views = glob(app_path('Extensions/AIChatPro*/resources/views/settings/*.blade.php')) ?: [];

    foreach ($views as $view) {
        $contents = file_get_contents($view);

        expect($contents)->not->toMatch('/name=["\'](?:ai_chat_pro_)?[^"\']*client_secret/i')
            ->and($contents)->not->toMatch('/value=["\'][^"\']*client_secret/i');
    }
});

it('keeps social media agent notifications compatible with the 10.9 notification menu', function () {
    $agent = new SocialMediaAgent(['name' => 'DZEVA QA Agent']);
    $agent->id = 123;

    $payload = (new PostGenerationCompletedNotification($agent, 2, 1))->toArray(new stdClass());
    $view = file_get_contents(resource_path('views/default/components/notifications.blade.php'));

    expect($payload)->toHaveKey('data')
        ->and($payload['data'])->toHaveKeys(['title', 'message', 'link'])
        ->and($payload)->toHaveKeys(['title', 'message', 'action_url'])
        ->and($view)->toContain("data_get(\$payload, 'data.title'")
        ->and($view)->toContain("'action_url'");
});

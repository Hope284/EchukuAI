<?php

declare(strict_types=1);

use App\Support\Security\SafeUrl;

it('accepts internal paths and http links', function () {
    expect(SafeUrl::normalize('/chat'))->toBe('/chat')
        ->and(SafeUrl::normalize('https://dzeva.com/blog'))->toBe('https://dzeva.com/blog')
        ->and(SafeUrl::isExternal('https://example.com', 'dzeva.com'))->toBeTrue()
        ->and(SafeUrl::isExternal('/dashboard/user', 'dzeva.com'))->toBeFalse();
});

it('rejects unsafe scrolling button protocols', function (string $url) {
    expect(SafeUrl::normalize($url))->toBeNull();
})->with([
    'javascript' => 'javascript:alert(1)',
    'data'       => 'data:text/html;base64,SGVsbG8=',
    'protocol relative' => '//evil.example/path',
    'backslash path'    => '/safe\\..\\unsafe',
]);

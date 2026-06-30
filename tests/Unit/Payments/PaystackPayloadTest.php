<?php

declare(strict_types=1);

use App\Models\UserOrder;
use App\Services\PaymentGateways\PaystackService;
use Illuminate\Http\Request;

function invokePrivatePaystack(string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod(PaystackService::class, $method);

    return $reflection->invoke(null, ...$arguments);
}

it('normalizes callback payloads that only contain a reference', function () {
    $request = Request::create('/callback', 'POST', [
        'response' => json_encode(['reference' => 'DZEVA-REF_1234'], JSON_THROW_ON_ERROR),
    ]);

    expect(invokePrivatePaystack('callbackPayload', $request)['reference'])->toBe('DZEVA-REF_1234');
});

it('rejects callback payloads without a valid reference', function () {
    $request = Request::create('/callback', 'POST', ['response' => '{}']);

    invokePrivatePaystack('callbackPayload', $request);
})->throws(RuntimeException::class, 'payment reference');

it('validates verified amount currency and customer', function () {
    $order = new UserOrder(['price' => 5000]);
    $transaction = [
        'amount'   => 500000,
        'currency' => 'NGN',
        'customer' => ['email' => 'buyer@example.com'],
    ];

    invokePrivatePaystack('assertVerifiedPayment', $transaction, $order, 'NGN', 'buyer@example.com');

    expect(true)->toBeTrue();
});

it('rejects a verified amount mismatch', function () {
    $order = new UserOrder(['price' => 5000]);

    invokePrivatePaystack('assertVerifiedPayment', [
        'amount'   => 100,
        'currency' => 'NGN',
        'customer' => ['email' => 'buyer@example.com'],
    ], $order, 'NGN', 'buyer@example.com');
})->throws(RuntimeException::class, 'amount does not match');

it('rejects a verified transaction without a customer identity', function () {
    $order = new UserOrder(['price' => 5000]);

    invokePrivatePaystack('assertVerifiedPayment', [
        'amount'   => 500000,
        'currency' => 'NGN',
        'customer' => [],
    ], $order, 'NGN', 'buyer@example.com');
})->throws(RuntimeException::class, 'customer does not match');

<?php

declare(strict_types=1);

use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Models\Gateways;
use App\Models\GatewayProducts;
use App\Models\ParentAffiliate;
use App\Models\ParentAffiliateChild;
use App\Models\ParentAffiliatePaymentGateway;
use App\Models\ParentAffiliateWithdrawal;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserOrder;
use App\Services\StrategicPartner\StrategicPartnerService;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

beforeEach(function () {
    Setting::factory()->create();
});

test('user profile settings save supported personal and address fields', function () {
    $user = User::factory()->create([
        'name' => 'Old',
        'surname' => 'Name',
    ]);

    $this->actingAs($user)
        ->post('/dashboard/user/settings/save', [
            'name' => 'Ada',
            'surname' => 'Okafor',
            'phone' => '+2348012345678',
            'address' => '12 Marina Road',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Lagos',
            'postal' => '100001',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'User settings saved successfully');

    $user->refresh();

    expect($user->name)->toBe('Ada')
        ->and($user->phone)->toBe('+2348012345678')
        ->and($user->address)->toBe('12 Marina Road')
        ->and($user->country)->toBe('Nigeria')
        ->and($user->state)->toBe('Lagos')
        ->and($user->city)->toBe('Lagos');
});

test('manual plan helper returns subscription and prepaid plans separately', function () {
    Plan::forgetCache();

    $monthly = Plan::factory()->create([
        'name' => 'Monthly Pro',
        'type' => TypeEnum::SUBSCRIPTION->value,
        'frequency' => FrequencyEnum::MONTHLY->value,
        'active' => true,
        'price' => 1000,
    ]);

    $yearly = Plan::factory()->create([
        'name' => 'Yearly Pro',
        'type' => TypeEnum::SUBSCRIPTION->value,
        'frequency' => FrequencyEnum::YEARLY->value,
        'active' => true,
        'price' => 10000,
    ]);

    $prepaid = Plan::factory()->create([
        'name' => 'Token Pack',
        'type' => TypeEnum::TOKEN_PACK->value,
        'frequency' => FrequencyEnum::PREPAID->value,
        'active' => true,
        'price' => 100,
    ]);

    Plan::forgetCache();

    expect(getSubsPlans()->pluck('id')->all())->toContain($monthly->id, $yearly->id)
        ->and(getTokenPlans()->pluck('id')->all())->toContain($prepaid->id);
});

test('strategic partner commission is twenty percent of child affiliate commission and idempotent', function () {
    $partnerUser = User::factory()->create();
    $childAffiliate = User::factory()->create(['affiliate_code' => 'CHILD123']);
    $customer = User::factory()->create(['affiliate_id' => $childAffiliate->id]);

    $partner = ParentAffiliate::query()->create([
        'user_id' => $partnerUser->id,
        'name' => 'Ghana Partner',
        'email' => 'partner@example.com',
        'country' => 'Ghana',
        'referral_code' => 'SP-GHANA',
        'status' => ParentAffiliate::STATUS_APPROVED,
        'commission_rate' => 20,
        'approved_at' => now(),
    ]);

    StrategicPartnerService::linkChildAffiliate($partner, $childAffiliate);

    $order = UserOrder::query()->create([
        'order_id' => 'ORD-1',
        'user_id' => $customer->id,
        'price' => 1000,
        'status' => 'Success',
        'payment_type' => 'paystack',
        'affiliate_earnings' => 100,
    ]);

    StrategicPartnerService::createCommissionForOrder($order->refresh());

    expect($childAffiliate->refresh()->affiliate_id)->toBeNull()
        ->and($order->affiliate_earnings)->toBe(100)
        ->and($partner->commissions()->count())->toBe(1)
        ->and((int) round($partner->commissions()->first()->amount))->toBe(20);
});

test('strategic partner withdrawal cannot exceed available balance', function () {
    $partner = ParentAffiliate::query()->create([
        'name' => 'Kenya Partner',
        'email' => 'kenya@example.com',
        'country' => 'Kenya',
        'referral_code' => 'SP-KENYA',
        'status' => ParentAffiliate::STATUS_APPROVED,
        'commission_rate' => 20,
        'approved_at' => now(),
    ]);

    expect(fn () => StrategicPartnerService::requestWithdrawal($partner, 50))
        ->toThrow(InvalidArgumentException::class);
});

test('strategic partner gateway rules filter checkout gateways with global fallback', function () {
    $partnerUser = User::factory()->create(['country' => 'Ghana']);
    $childAffiliate = User::factory()->create(['country' => 'Ghana']);
    $customer = User::factory()->create(['affiliate_id' => $childAffiliate->id, 'country' => 'Ghana']);

    $partner = ParentAffiliate::query()->create([
        'user_id' => $partnerUser->id,
        'name' => 'Ghana Partner',
        'email' => 'gateway@example.com',
        'country' => 'Ghana',
        'referral_code' => 'SP-GATEWAY',
        'status' => ParentAffiliate::STATUS_APPROVED,
        'commission_rate' => 20,
        'approved_at' => now(),
    ]);
    ParentAffiliateChild::query()->create([
        'parent_affiliate_id' => $partner->id,
        'child_affiliate_user_id' => $childAffiliate->id,
        'child_affiliate_code' => $childAffiliate->affiliate_code,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    Gateways::query()->create(['code' => 'paystack', 'title' => 'Paystack', 'is_active' => true]);
    Gateways::query()->create(['code' => 'stripe', 'title' => 'Stripe', 'is_active' => true]);

    ParentAffiliatePaymentGateway::query()->create([
        'parent_affiliate_id' => $partner->id,
        'gateway_key' => 'paystack',
        'country' => 'Ghana',
        'is_enabled' => true,
    ]);

    expect(StrategicPartnerService::availableGateways($customer)->pluck('code')->all())->toBe(['paystack'])
        ->and(StrategicPartnerService::gatewayAllowed($customer, 'stripe'))->toBeFalse();
});

test('local gateway product command creates missing mappings without duplicates', function () {
    $plan = Plan::factory()->create([
        'name' => 'Starter',
        'type' => TypeEnum::SUBSCRIPTION->value,
        'frequency' => FrequencyEnum::MONTHLY->value,
        'active' => true,
        'price' => 1000,
    ]);
    Gateways::query()->create(['code' => 'freeservice', 'title' => 'Free Service', 'is_active' => true]);

    artisan('plans:ensure-gateway-products')->assertSuccessful();
    artisan('plans:ensure-gateway-products')->assertSuccessful();

    expect(GatewayProducts::query()
        ->where('plan_id', $plan->id)
        ->where('gateway_code', 'freeservice')
        ->count())->toBe(1);
});

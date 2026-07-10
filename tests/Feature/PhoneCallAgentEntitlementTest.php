<?php

declare(strict_types=1);

use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserOrder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Subscription;

function dzevaPhoneCallPlan(array $attributes = []): Plan
{
    return Plan::factory()->create(array_merge([
        'active'                         => true,
        'type'                           => TypeEnum::SUBSCRIPTION->value,
        'frequency'                      => FrequencyEnum::LIFETIME->value,
        'plan_ai_tools'                  => ['ext_phone_call_agent' => true],
        'plan_features'                  => ['ext_phone_call_agent' => true],
        'phone_call_agent_seconds_limit' => -1,
        'ai_models'                      => [],
        'is_team_plan'                   => false,
        'price'                          => 5000,
    ], $attributes));
}

test('active plan lookup prefers lifetime access with phone call agent over an older starter subscription', function () {
    $user = User::factory()->create();
    $starter = dzevaPhoneCallPlan([
        'name'                           => 'Starter',
        'price'                          => 10000,
        'frequency'                      => FrequencyEnum::MONTHLY->value,
        'plan_ai_tools'                  => [],
        'plan_features'                  => [],
        'phone_call_agent_seconds_limit' => 0,
    ]);
    $lifetime = dzevaPhoneCallPlan(['name' => 'Lifetime Access']);

    Subscription::query()->create([
        'user_id'       => $user->id,
        'plan_id'       => $starter->id,
        'name'          => (string) $starter->id,
        'stripe_id'     => 'FLS-STARTER',
        'stripe_status' => 'paystack_approved',
        'stripe_price'  => 'starter',
        'quantity'      => 1,
        'ends_at'       => now()->addMonth(),
        'paid_with'     => 'paystack',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    Subscription::query()->create([
        'user_id'       => $user->id,
        'plan_id'       => $lifetime->id,
        'name'          => (string) $lifetime->id,
        'stripe_id'     => 'FLS-LIFETIME',
        'stripe_status' => 'paystack_approved',
        'stripe_price'  => 'Not Needed',
        'quantity'      => 1,
        'ends_at'       => null,
        'paid_with'     => 'paystack',
        'created_at'    => now()->subDay(),
        'updated_at'    => now()->subDay(),
    ]);

    Cache::flush();

    $plan = $user->fresh()->activePlan();

    expect($plan?->id)->toBe($lifetime->id)
        ->and($plan?->checkOpenAiItem('ext_phone_call_agent'))->toBeTrue();
});

test('premium entitlement sync repairs successful lifetime orders missing subscription rows', function () {
    $user = User::factory()->create();
    $lifetime = dzevaPhoneCallPlan(['name' => 'Lifetime Access']);

    UserOrder::query()->create([
        'order_id'     => 'FLS-LIFETIME-ORDER',
        'plan_id'      => $lifetime->id,
        'payment_type' => 'paystack',
        'price'        => 5000,
        'status'       => 'Success',
        'country'      => 'Nigeria',
        'user_id'      => $user->id,
        'type'         => 'subscription',
    ]);

    Artisan::call('dzeva:sync-premium-entitlements');
    Cache::flush();

    $subscription = Subscription::query()
        ->where('stripe_id', 'FLS-LIFETIME-ORDER')
        ->first();

    expect($subscription)->not->toBeNull()
        ->and((int) $subscription->user_id)->toBe($user->id)
        ->and((int) $subscription->plan_id)->toBe($lifetime->id)
        ->and($subscription->stripe_status)->toBe('paystack_approved')
        ->and($subscription->ends_at)->toBeNull()
        ->and($user->fresh()->activePlan()?->checkOpenAiItem('ext_phone_call_agent'))->toBeTrue();
});

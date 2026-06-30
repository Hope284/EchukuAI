<?php

declare(strict_types=1);

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\SettingTwo;
use App\Support\Dzeva\DzevaModelCatalog;
use Database\Seeders\DzevaPlanSeeder;
use Database\Seeders\EngineSeeder;
use Database\Seeders\EntitySeeder;
use Database\Seeders\TokenSeeder;

beforeEach(function () {
    Setting::factory()->create();
    SettingTwo::factory()->create();

    $this->seed([
        EngineSeeder::class,
        EntitySeeder::class,
        TokenSeeder::class,
        DzevaPlanSeeder::class,
    ]);
});

test('required dzeva subscription plans are seeded in the existing plans table', function () {
    $expectedPlans = [
        'Starter'           => [10000, FrequencyEnum::MONTHLY->value],
        'Growth'            => [25000, FrequencyEnum::MONTHLY->value],
        'Business'          => [100000, FrequencyEnum::MONTHLY->value],
        'Scale'             => [600000, FrequencyEnum::MONTHLY->value],
        'Enterprise'        => [2700000, FrequencyEnum::MONTHLY->value],
        'Starter Yearly'    => [108000, FrequencyEnum::YEARLY->value],
        'Growth Yearly'     => [276000, FrequencyEnum::YEARLY->value],
        'Business Yearly'   => [1116000, FrequencyEnum::YEARLY->value],
        'Scale Yearly'      => [6768000, FrequencyEnum::YEARLY->value],
        'Enterprise Yearly' => [30780000, FrequencyEnum::YEARLY->value],
        'Lifetime Access'   => [5000, FrequencyEnum::LIFETIME->value],
    ];

    foreach ($expectedPlans as $name => [$price, $frequency]) {
        $plan = Plan::query()
            ->where('name', $name)
            ->where('type', TypeEnum::SUBSCRIPTION->value)
            ->first();

        expect($plan)->not->toBeNull()
            ->and((int) $plan->price)->toBe($price)
            ->and($plan->frequency)->toBe($frequency)
            ->and($plan->currency)->toBe('NGN')
            ->and($plan->active)->toBeTrue()
            ->and((bool) $plan->hidden)->toBeFalse();
    }
});

test('lifetime access is premium while keeping numeric included credits at zero', function () {
    $plan = Plan::query()
        ->where('name', 'Lifetime Access')
        ->where('type', TypeEnum::SUBSCRIPTION->value)
        ->firstOrFail();

    expect((int) $plan->max_tokens)->toBe(0);

    foreach ($plan->ai_models as $models) {
        foreach ($models as $limits) {
            expect((float) ($limits['credit'] ?? 0))->toBe(0.0)
                ->and((bool) ($limits['isUnlimited'] ?? false))->toBeTrue();
        }
    }

    expect($plan->is_team_plan)->toBeTrue()
        ->and((int) $plan->plan_allow_seat)->toBe(-1)
        ->and((int) $plan->chatbot_limit)->toBe(-1)
        ->and((int) $plan->ai_agent_workflow_limit)->toBe(-1)
        ->and((int) $plan->ai_captions_minutes)->toBe(-1)
        ->and(collect($plan->plan_ai_tools)->every(fn ($enabled) => $enabled === true))->toBeTrue()
        ->and(collect($plan->plan_features)->every(fn ($enabled) => $enabled === true))->toBeTrue();
});

test('thirty two dzeva prepaid capability token plans are seeded', function () {
    $tokenSizes = [
        '10K'  => 10000,
        '50K'  => 50000,
        '100K' => 100000,
        '500K' => 500000,
    ];

    foreach (DzevaModelCatalog::capabilities() as $capability) {
        foreach ($tokenSizes as $label => $quantity) {
            $plan = Plan::query()
                ->where('name', $capability['name'] . ' ' . $label . ' Tokens')
                ->where('type', TypeEnum::TOKEN_PACK->value)
                ->first();

            expect($plan)->not->toBeNull()
                ->and($plan->frequency)->toBe(FrequencyEnum::PREPAID->value)
                ->and((int) $plan->max_tokens)->toBe($quantity)
                ->and($plan->currency)->toBe('NGN')
                ->and($plan->features)->toContain(number_format($quantity) . ' Dzeva tokens');
        }
    }

    expect(Plan::query()
        ->where('type', TypeEnum::TOKEN_PACK->value)
        ->where(function ($query) {
            foreach (DzevaModelCatalog::capabilities() as $capability) {
                $query->orWhere('name', 'like', $capability['name'] . ' % Tokens');
            }
        })
        ->count())->toBe(32);
});

test('public model labels and normal api payloads hide backend provider names', function () {
    foreach (DzevaModelCatalog::capabilities() as $capability) {
        expect(DzevaModelCatalog::containsForbiddenProviderName($capability['entity']->label()))->toBeFalse();

        $entity = Entity::query()->where('key', $capability['entity']->value)->firstOrFail();
        $payload = DzevaModelCatalog::publicEntityPayload($entity);

        expect(DzevaModelCatalog::containsForbiddenProviderName(json_encode($payload, JSON_THROW_ON_ERROR)))->toBeFalse();
    }

    expect(DzevaModelCatalog::containsForbiddenProviderName(EntityEnum::GPT_5_MINI->label()))->toBeFalse();
});

test('prepaid token pricing preserves at least two hundred percent profit', function () {
    $tokenSizes = [
        '10K'  => 10000,
        '50K'  => 50000,
        '100K' => 100000,
        '500K' => 500000,
    ];

    foreach (DzevaModelCatalog::capabilities() as $capability) {
        foreach ($tokenSizes as $label => $quantity) {
            $plan = Plan::query()
                ->where('name', $capability['name'] . ' ' . $label . ' Tokens')
                ->where('type', TypeEnum::TOKEN_PACK->value)
                ->firstOrFail();

            $providerCost = $quantity * $capability['entity']->unitPrice() * DzevaModelCatalog::exchangeRateNgnPerUsd();

            expect((float) $plan->price)->toBeGreaterThanOrEqual($providerCost * DzevaModelCatalog::MARKUP_MULTIPLIER);
        }
    }
});

test('session and remember me durations match dzeva requirements', function () {
    expect((int) config('session.lifetime'))->toBe(40320)
        ->and((int) config('auth.remember_lifetime'))->toBe(70560);
});

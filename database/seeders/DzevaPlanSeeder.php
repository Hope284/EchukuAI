<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Engine\Enums\EngineEnum;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Models\Entity;
use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Enums\StatusEnum;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\Finance\PlanService;
use App\Support\Dzeva\DzevaModelCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DzevaPlanSeeder extends Seeder
{
    /**
     * @var array<int, int>
     */
    private array $tokenSizes = [
        10000,
        50000,
        100000,
        500000,
    ];

    public function run(): void
    {
        $this->seedNgnCurrency();
        $this->seedPublicEntityLabels();
        $this->seedSubscriptionPlans();
        $this->seedPrepaidCapabilityPlans();

        PlanService::clearCache();
    }

    private function seedNgnCurrency(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        $currency = Currency::query()->updateOrCreate(
            ['code' => 'NGN'],
            [
                'country'            => 'Nigeria',
                'currency'           => 'Nigerian Naira',
                'symbol'             => '₦',
                'thousand_separator' => ',',
                'decimal_separator'  => '.',
            ]
        );

        if (Schema::hasTable('settings')) {
            Setting::query()->update(['default_currency' => (string) $currency->id]);
            Setting::forgetCache();
            Currency::forgetCache();
        }
    }

    private function seedPublicEntityLabels(): void
    {
        foreach (DzevaModelCatalog::capabilities() as $capability) {
            Entity::query()
                ->where('key', $capability['entity']->value)
                ->update([
                    'selected_title' => $capability['name'],
                    'title'          => $capability['name'] . ' - ' . $capability['capability'] . '. ' . $capability['description'],
                    'is_selected'    => true,
                    'status'         => StatusEnum::ENABLED->value,
                ]);
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'name'        => 'Starter',
                'frequency'   => FrequencyEnum::MONTHLY->value,
                'price'       => 10000,
                'featured'    => false,
                'description' => 'Good for individuals and very small businesses starting with Dzeva.',
                'capabilities' => ['ogbon', 'sabi', 'sani'],
            ],
            [
                'name'        => 'Growth',
                'frequency'   => FrequencyEnum::MONTHLY->value,
                'price'       => 25000,
                'featured'    => true,
                'description' => 'Good for small businesses that need chat, writing, memory, and support workflows.',
                'capabilities' => ['ogbon', 'sabi', 'sani', 'hikima'],
            ],
            [
                'name'        => 'Business',
                'frequency'   => FrequencyEnum::MONTHLY->value,
                'price'       => 100000,
                'featured'    => false,
                'description' => 'Good for teams using Dzeva for documents, automation, business memory, and support.',
                'capabilities' => ['ogbon', 'amamihe', 'hikima', 'sani', 'akili'],
            ],
            [
                'name'        => 'Scale',
                'frequency'   => FrequencyEnum::MONTHLY->value,
                'price'       => 600000,
                'featured'    => false,
                'description' => 'Good for growing companies with heavier automation, memory, documents, media, and support needs.',
                'capabilities' => array_keys(DzevaModelCatalog::capabilities()),
            ],
            [
                'name'        => 'Enterprise',
                'frequency'   => FrequencyEnum::MONTHLY->value,
                'price'       => 2700000,
                'featured'    => false,
                'description' => 'Good for large organizations that need full Dzeva access, team controls, and the highest included credits.',
                'capabilities' => array_keys(DzevaModelCatalog::capabilities()),
            ],
            [
                'name'        => 'Starter Yearly',
                'frequency'   => FrequencyEnum::YEARLY->value,
                'price'       => 108000,
                'featured'    => false,
                'description' => 'Starter access billed yearly with a 10% yearly discount.',
                'capabilities' => ['ogbon', 'sabi', 'sani'],
            ],
            [
                'name'        => 'Growth Yearly',
                'frequency'   => FrequencyEnum::YEARLY->value,
                'price'       => 276000,
                'featured'    => true,
                'description' => 'Growth access billed yearly with an 8% yearly discount.',
                'capabilities' => ['ogbon', 'sabi', 'sani', 'hikima'],
            ],
            [
                'name'        => 'Business Yearly',
                'frequency'   => FrequencyEnum::YEARLY->value,
                'price'       => 1116000,
                'featured'    => false,
                'description' => 'Business access billed yearly with a 7% yearly discount.',
                'capabilities' => ['ogbon', 'amamihe', 'hikima', 'sani', 'akili'],
            ],
            [
                'name'        => 'Scale Yearly',
                'frequency'   => FrequencyEnum::YEARLY->value,
                'price'       => 6768000,
                'featured'    => false,
                'description' => 'Scale access billed yearly with a 6% yearly discount.',
                'capabilities' => array_keys(DzevaModelCatalog::capabilities()),
            ],
            [
                'name'        => 'Enterprise Yearly',
                'frequency'   => FrequencyEnum::YEARLY->value,
                'price'       => 30780000,
                'featured'    => false,
                'description' => 'Enterprise access billed yearly with a 5% yearly discount.',
                'capabilities' => array_keys(DzevaModelCatalog::capabilities()),
            ],
        ];

        foreach ($plans as $plan) {
            $credits = $this->subscriptionCredits($plan['price'], $plan['capabilities']);
            $capabilityNames = $this->capabilityNames($plan['capabilities']);

            $this->persistPlan(TypeEnum::SUBSCRIPTION->value, [
                'active'                   => true,
                'hidden'                   => false,
                'name'                     => $plan['name'],
                'description'              => $plan['description'],
                'features'                 => implode(',', [
                    'Includes ' . number_format(array_sum($credits)) . ' Dzeva credits',
                    'Access: ' . implode(' | ', $capabilityNames),
                    'Credits reset on renewal',
                    'Buy prepaid Dzeva token plans for extra usage',
                ]),
                'price'                    => $plan['price'],
                'currency'                 => 'NGN',
                'frequency'                => $plan['frequency'],
                'type'                     => TypeEnum::SUBSCRIPTION->value,
                'is_featured'              => $plan['featured'],
                'max_tokens'               => array_sum($credits),
                'ai_name'                  => 'Dzeva',
                'default_ai_model'         => $this->entityForCapability($plan['capabilities'][0])->slug(),
                'can_create_ai_images'     => in_array('taswira', $plan['capabilities'], true),
                'ai_models'                => $this->aiModelsForCredits($credits),
                'reset_credits_on_renewal' => true,
                'multi_model_support'      => true,
                'model_council_support'    => in_array($plan['name'], ['Scale', 'Enterprise', 'Scale Yearly', 'Enterprise Yearly'], true),
                'voice_call_seconds_limit' => in_array('ohun', $plan['capabilities'], true) ? -1 : 0,
            ]);
        }

        $this->persistPlan(TypeEnum::SUBSCRIPTION->value, [
            'active'                   => true,
            'hidden'                   => false,
            'name'                     => 'Lifetime Access',
            'description'              => 'One-time Dzeva feature unlock for users who prefer prepaid token usage.',
            'features'                 => implode(',', [
                'All Dzeva platform features unlocked',
                'Zero included Dzeva credits',
                'Buy prepaid token plans before using paid AI models',
                'Best for pay-as-you-go usage',
            ]),
            'price'                    => 5000,
            'currency'                 => 'NGN',
            'frequency'                => FrequencyEnum::LIFETIME->value,
            'type'                     => TypeEnum::SUBSCRIPTION->value,
            'is_featured'              => false,
            'max_tokens'               => 0,
            'ai_name'                  => 'Dzeva',
            'default_ai_model'         => EntityEnum::GPT_5_MINI->slug(),
            'can_create_ai_images'     => true,
            'ai_models'                => $this->aiModelsForCredits([]),
            'reset_credits_on_renewal' => false,
            'multi_model_support'      => true,
            'model_council_support'    => true,
            'voice_call_seconds_limit' => -1,
        ]);
    }

    private function seedPrepaidCapabilityPlans(): void
    {
        foreach (DzevaModelCatalog::capabilities() as $capabilityKey => $capability) {
            foreach ($this->tokenSizes as $tokenQuantity) {
                $label = $this->tokenQuantityLabel($tokenQuantity);
                $price = $this->prepaidPrice($capability['entity'], $tokenQuantity);

                $this->persistPlan(TypeEnum::TOKEN_PACK->value, [
                    'active'               => true,
                    'hidden'               => false,
                    'name'                 => $capability['name'] . ' ' . $label . ' Tokens',
                    'description'          => $capability['icon'] . ' | ' . $capability['capability'] . ' | ' . $capability['description'],
                    'features'             => implode(',', [
                        number_format($tokenQuantity) . ' Dzeva tokens',
                        'Capability: ' . $capability['name'] . ' - ' . $capability['capability'],
                        'Icon: ' . $capability['icon'],
                        'Prepaid token plan',
                    ]),
                    'price'                => $price,
                    'currency'             => 'NGN',
                    'frequency'            => FrequencyEnum::PREPAID->value,
                    'type'                 => TypeEnum::TOKEN_PACK->value,
                    'is_featured'          => $tokenQuantity === 100000,
                    'max_tokens'           => $tokenQuantity,
                    'ai_name'              => 'Dzeva',
                    'default_ai_model'     => $capability['entity']->slug(),
                    'can_create_ai_images' => $capabilityKey === 'taswira',
                    'ai_models'            => $this->aiModelsForCredits([$capabilityKey => $tokenQuantity]),
                ]);
            }
        }
    }

    /**
     * @param array<string, int> $credits
     */
    private function aiModelsForCredits(array $credits): array
    {
        $aiModels = EngineEnum::getNestedPlanLimits();

        foreach (DzevaModelCatalog::capabilities() as $capabilityKey => $capability) {
            $entity = $capability['entity'];
            $engineSlug = $entity->engine()->slug();
            $entitySlug = $entity->slug();

            if (! isset($aiModels[$engineSlug][$entitySlug])) {
                continue;
            }

            $aiModels[$engineSlug][$entitySlug] = [
                'credit'      => (float) ($credits[$capabilityKey] ?? 0),
                'isUnlimited' => false,
            ];
        }

        return $aiModels;
    }

    /**
     * @param array<int, string> $capabilityKeys
     * @return array<string, int>
     */
    private function subscriptionCredits(int $price, array $capabilityKeys): array
    {
        if ($price <= 0 || $capabilityKeys === []) {
            return [];
        }

        $usableProviderBudget = ($price / DzevaModelCatalog::MARKUP_MULTIPLIER) * DzevaModelCatalog::SUBSCRIPTION_BUFFER;
        $budgetPerCapability = $usableProviderBudget / count($capabilityKeys);
        $credits = [];

        foreach ($capabilityKeys as $capabilityKey) {
            $entity = $this->entityForCapability($capabilityKey);
            $providerCostPerCredit = max($entity->unitPrice() * DzevaModelCatalog::exchangeRateNgnPerUsd(), 0.000001);
            $credits[$capabilityKey] = max(1, (int) floor($budgetPerCapability / $providerCostPerCredit));
        }

        return $credits;
    }

    private function prepaidPrice(EntityEnum $entity, int $tokenQuantity): int
    {
        $providerCost = $tokenQuantity * $entity->unitPrice() * DzevaModelCatalog::exchangeRateNgnPerUsd();
        $minimumSellingPrice = $providerCost * DzevaModelCatalog::MARKUP_MULTIPLIER;

        return max(
            $this->minimumRetailPrice($tokenQuantity),
            (int) (ceil($minimumSellingPrice / 100) * 100)
        );
    }

    private function minimumRetailPrice(int $tokenQuantity): int
    {
        return match ($tokenQuantity) {
            10000  => 1000,
            50000  => 4000,
            100000 => 7500,
            500000 => 30000,
            default => 1000,
        };
    }

    /**
     * @param array<int, string> $capabilityKeys
     * @return array<int, string>
     */
    private function capabilityNames(array $capabilityKeys): array
    {
        $capabilities = DzevaModelCatalog::capabilities();

        return collect($capabilityKeys)
            ->map(static fn (string $capabilityKey): string => $capabilities[$capabilityKey]['name'] . ' - ' . $capabilities[$capabilityKey]['capability'])
            ->all();
    }

    private function entityForCapability(string $capabilityKey): EntityEnum
    {
        return DzevaModelCatalog::capabilities()[$capabilityKey]['entity'];
    }

    private function tokenQuantityLabel(int $tokenQuantity): string
    {
        return match ($tokenQuantity) {
            10000   => '10K',
            50000   => '50K',
            100000  => '100K',
            500000  => '500K',
            1000000 => '1M',
            default => number_format($tokenQuantity),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function persistPlan(string $type, array $attributes): Plan
    {
        $plan = Plan::query()
            ->where('name', $attributes['name'])
            ->where('type', $type)
            ->first();

        if (! $plan) {
            $plan = $type === TypeEnum::SUBSCRIPTION->value
                ? Plan::createFreshPlan($attributes)
                : Plan::createFreshTokenPackPlan($attributes);
        } else {
            $plan->fill($attributes);
        }

        $plan->save();

        return $plan;
    }
}

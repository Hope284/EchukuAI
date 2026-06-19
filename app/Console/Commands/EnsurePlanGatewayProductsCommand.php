<?php

namespace App\Console\Commands;

use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\Plan;
use App\Services\GatewaySelector;
use App\Services\Payment\Enums\PaymentGatewayEnum;
use App\Services\Payment\Factories\GatewayFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EnsurePlanGatewayProductsCommand extends Command
{
    protected $signature = 'plans:ensure-gateway-products {--gateway= : Limit to one gateway code}';

    protected $description = 'Ensure active plans have missing gateway product/price mappings without duplicating existing mappings.';

    public function handle(): int
    {
        $plans = Plan::query()->where('active', 1)->orderBy('id')->get();
        $gateways = Gateways::query()
            ->where('is_active', 1)
            ->when($this->option('gateway'), fn ($query, $gateway) => $query->where('code', $gateway))
            ->orderBy('code')
            ->get();

        if ($plans->isEmpty() || $gateways->isEmpty()) {
            $this->warn('No active plans or active gateways found.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($gateways as $gateway) {
            foreach ($plans as $plan) {
                $mapping = GatewayProducts::query()
                    ->where('plan_id', $plan->id)
                    ->where('gateway_code', $gateway->code)
                    ->first();

                if ($mapping?->product_id && $mapping?->price_id) {
                    $skipped++;
                    continue;
                }

                if ($this->isLocalGateway($gateway->code)) {
                    $this->createLocalMapping($gateway, $plan, $mapping);
                    $created++;
                    continue;
                }

                if (! $this->hasGatewayCredentials($gateway)) {
                    $this->warn("Skipped {$gateway->code} for plan {$plan->id}: missing gateway credentials.");
                    $skipped++;
                    continue;
                }

                try {
                    if (PaymentGatewayEnum::isRefactored($gateway->code)) {
                        GatewayFactory::make(PaymentGatewayEnum::tryFrom($gateway->code))->saveProduct($plan);
                    } else {
                        $service = GatewaySelector::selectGateway($gateway->code);

                        if (! method_exists($service, 'saveProduct')) {
                            $this->warn("Skipped {$gateway->code}: saveProduct is not available.");
                            $skipped++;
                            continue;
                        }

                        $serviceClass = get_class($service);
                        $serviceClass::saveProduct($plan);
                    }

                    $mapping = GatewayProducts::query()
                        ->where('plan_id', $plan->id)
                        ->where('gateway_code', $gateway->code)
                        ->first();

                    if (! $mapping?->product_id || ! $mapping?->price_id) {
                        throw new \RuntimeException('Gateway did not persist a complete product mapping.');
                    }

                    $created++;
                    $this->line("Ensured {$gateway->code} mapping for plan {$plan->id}.");
                } catch (Throwable $exception) {
                    $errors++;
                    Log::warning('plans:ensure-gateway-products failed', [
                        'gateway' => $gateway->code,
                        'plan_id' => $plan->id,
                        'error' => $exception->getMessage(),
                    ]);
                    $this->error("Failed {$gateway->code} for plan {$plan->id}: {$exception->getMessage()}");
                }
            }
        }

        $this->info("Gateway product check complete. Created/updated: {$created}; skipped: {$skipped}; errors: {$errors}.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function createLocalMapping(Gateways $gateway, Plan $plan, ?GatewayProducts $mapping): void
    {
        $mapping ??= new GatewayProducts;
        $mapping->plan_id = $plan->id;
        $mapping->plan_name = $plan->name;
        $mapping->gateway_code = $gateway->code;
        $mapping->gateway_title = $gateway->title ?: Str::title($gateway->code);
        $mapping->product_id = $mapping->product_id ?: 'DZV-' . Str::upper($gateway->code) . '-PLAN-' . $plan->id;
        $mapping->price_id = $mapping->price_id ?: $this->localPriceId($gateway, $plan);
        $mapping->save();

        $this->line("Created local {$gateway->code} mapping for plan {$plan->id}.");
    }

    private function localPriceId(Gateways $gateway, Plan $plan): string
    {
        if ($plan->type === TypeEnum::TOKEN_PACK->value || (float) $plan->price <= 0 || $plan->frequency === FrequencyEnum::LIFETIME->value) {
            return 'Not Needed';
        }

        return 'DZV-' . Str::upper($gateway->code) . '-PRICE-' . $plan->id . '-' . Str::upper((string) $plan->frequency);
    }

    private function isLocalGateway(string $gatewayCode): bool
    {
        return in_array($gatewayCode, ['freeservice', 'banktransfer', 'transfer'], true);
    }

    private function hasGatewayCredentials(Gateways $gateway): bool
    {
        return $gateway->isSandbox()
            ? filled($gateway->sandbox_client_id) || filled($gateway->sandbox_client_secret)
            : filled($gateway->live_client_id) || filled($gateway->live_client_secret) || filled($gateway->live_app_id);
    }
}

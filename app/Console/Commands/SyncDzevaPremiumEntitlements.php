<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plan;
use App\Services\PaymentGateways\Contracts\CreditUpdater;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

class SyncDzevaPremiumEntitlements extends Command
{
    use CreditUpdater;

    protected $signature = 'dzeva:sync-premium-entitlements {--dry-run}';

    protected $description = 'Apply the current Lifetime Access entitlements to active subscribers.';

    public function handle(): int
    {
        $plan = Plan::query()
            ->where('name', 'Lifetime Access')
            ->where('price', 5000)
            ->first();

        if (! $plan) {
            $this->warn('Lifetime Access plan was not found.');

            return self::SUCCESS;
        }

        $query = Subscription::query()
            ->with('user')
            ->where('plan_id', $plan->id)
            ->whereNotIn('stripe_status', ['cancelled', 'canceled', 'inactive', 'expired'])
            ->where(static function ($builder) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });

        $count = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("{$count} active subscriber(s) require the current premium entitlements.");

            return self::SUCCESS;
        }

        $updated = 0;
        $query->chunkById(100, function ($subscriptions) use ($plan, &$updated): void {
            foreach ($subscriptions as $subscription) {
                if (! $subscription->user) {
                    continue;
                }

                self::creditIncreaseSubscribePlan($subscription->user, $plan);
                $updated++;
            }
        });

        $this->info("Premium entitlements synchronized for {$updated} active subscriber(s).");

        return self::SUCCESS;
    }
}

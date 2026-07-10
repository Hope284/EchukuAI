<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\UserOrder;
use App\Services\PaymentGateways\Contracts\CreditUpdater;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;
use Throwable;

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

        $missingSubscriptions = $this->missingSuccessfulLifetimeOrderSubscriptions($plan);

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
            $this->info("{$missingSubscriptions} successful Lifetime Access order(s) require subscription repair.");

            return self::SUCCESS;
        }

        $repaired = $this->repairSuccessfulLifetimeOrderSubscriptions($plan);

        $updated = 0;
        $query->chunkById(100, function ($subscriptions) use ($plan, &$updated): void {
            foreach ($subscriptions as $subscription) {
                if (! $subscription->user) {
                    continue;
                }

                try {
                    self::creditIncreaseSubscribePlan($subscription->user, $plan);
                    $updated++;
                } catch (Throwable $exception) {
                    $this->warn(sprintf(
                        'Skipped premium credit sync for subscription %s/user %s: %s',
                        $subscription->id,
                        $subscription->user_id,
                        $exception->getMessage(),
                    ));
                }
            }
        });

        $this->info("Premium entitlements synchronized for {$updated} active subscriber(s); repaired {$repaired} Lifetime Access subscription(s).");

        return self::SUCCESS;
    }

    private function missingSuccessfulLifetimeOrderSubscriptions(Plan $plan): int
    {
        return UserOrder::query()
            ->where('plan_id', $plan->id)
            ->where('status', 'Success')
            ->where('type', 'subscription')
            ->whereNotNull('user_id')
            ->whereNotNull('order_id')
            ->whereNotExists(static function ($query): void {
                $query->selectRaw('1')
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.stripe_id', 'user_orders.order_id');
            })
            ->count();
    }

    private function repairSuccessfulLifetimeOrderSubscriptions(Plan $plan): int
    {
        $repaired = 0;

        UserOrder::query()
            ->with('user')
            ->where('plan_id', $plan->id)
            ->where('status', 'Success')
            ->where('type', 'subscription')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($plan, &$repaired): void {
                foreach ($orders as $order) {
                    if (! $order->user) {
                        continue;
                    }

                    $subscriptionId = $order->order_id ?: 'DZEVA-LIFETIME-ORDER-' . $order->id;
                    $subscription = Subscription::query()
                        ->where('stripe_id', $subscriptionId)
                        ->first();

                    if ($subscription && (int) $subscription->user_id !== (int) $order->user_id) {
                        $this->warn("Skipped Lifetime Access order {$order->id}; subscription id belongs to another user.");
                        continue;
                    }

                    $subscription ??= new Subscription;
                    $subscription->stripe_id = $subscriptionId;
                    $subscription->stripe_price = 'Not Needed';
                    $subscription->stripe_status = 'paystack_approved';
                    $subscription->ends_at = null;
                    $subscription->auto_renewal = 0;
                    $subscription->user_id = $order->user_id;
                    $subscription->name = (string) $plan->id;
                    $subscription->quantity = 1;
                    $subscription->plan_id = $plan->id;
                    $subscription->paid_with = $order->payment_type ?: 'paystack';
                    $subscription->tax_rate = $order->tax_rate;
                    $subscription->tax_value = $order->tax_value;
                    $subscription->coupon = null;
                    $subscription->total_amount = $order->price;

                    if ($subscription->isDirty()) {
                        $subscription->save();
                        $repaired++;
                    }
                }
            });

        return $repaired;
    }
}

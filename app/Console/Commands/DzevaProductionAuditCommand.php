<?php

namespace App\Console\Commands;

use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\Plan;
use App\Models\Extension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DzevaProductionAuditCommand extends Command
{
    protected $signature = 'dzeva:production-audit';

    protected $description = 'Report DZEVA production readiness without exposing credentials.';

    public function handle(): int
    {
        $failed = false;

        foreach ([
            'plans',
            'gatewayproducts',
            'parent_affiliates',
            'parent_affiliate_children',
            'parent_affiliate_commissions',
            'parent_affiliate_withdrawals',
            'shared_credit_transactions',
            'shared_credit_costs',
            'ext_ai_agent_workflows',
            'ext_ai_agent_channels',
            'ext_ai_agent_memories',
            'ext_ai_chat_pro_connectors',
            'ext_phone_call_agents',
            'ext_phone_call_agent_calls',
            'ext_phone_call_agent_trains',
        ] as $table) {
            $exists = Schema::hasTable($table);
            $this->{$exists ? 'info' : 'error'}("table {$table}: " . ($exists ? 'ready' : 'missing'));
            $failed = $failed || ! $exists;
        }

        foreach ([
            'dashboard.user.strategic-partner.index',
            'dashboard.admin.strategic-partners.index',
            'dashboard.admin.strategic-partners.create',
            'strategic-partner.referral',
            'dashboard.phone-call-agent.index',
            'dashboard.user.ai-chat-pro.connectors.index',
        ] as $route) {
            $exists = Route::has($route);
            $this->{$exists ? 'info' : 'error'}("route {$route}: " . ($exists ? 'ready' : 'missing'));
            $failed = $failed || ! $exists;
        }

        $this->line('Amamihe credential: ' . (filled(setting('gemini_api_secret')) ? 'configured' : 'missing'));
        $this->line('Hikima credential: ' . (filled(setting('anthropic_api_secret')) ? 'configured' : 'missing'));

        foreach ([
            'ai-chat-pro'                 => '3.7',
            'ai-agent'                    => '1.1',
            'phone-call-agent'            => '1.0',
            'ai-chat-pro-gmail'           => '1.0',
            'ai-chat-pro-google-calendar' => '1.0',
            'ai-chat-pro-google-drive'    => '1.0',
            'ai-chat-pro-notion'          => '1.0',
            'ai-chat-pro-outlook'         => '1.0',
        ] as $slug => $version) {
            $extension = Extension::query()->where('slug', $slug)->first();
            $ready = $extension?->installed && (string) $extension?->version === $version;
            $this->{$ready ? 'info' : 'error'}("extension {$slug}: " . ($ready ? "ready ({$version})" : 'missing or inactive'));
            $failed = $failed || ! $ready;
        }

        $subscriptionPlans = Plan::query()->where('active', 1)->where('type', 'subscription')->count();
        $prepaidPlans = Plan::query()->where('active', 1)->where('type', 'prepaid')->count();
        $this->info("active plans: subscription={$subscriptionPlans}; prepaid={$prepaidPlans}");

        $phoneCallEligiblePlans = [
            'Scale',
            'Enterprise',
            'Scale Yearly',
            'Enterprise Yearly',
            'Lifetime Access',
        ];

        foreach (Plan::query()->whereIn('name', $phoneCallEligiblePlans)->orderBy('name')->get() as $plan) {
            $enabled = $plan->checkOpenAiItem('ext_phone_call_agent') && (int) $plan->phone_call_agent_seconds_limit === -1;
            $this->{$enabled ? 'info' : 'error'}("phone-call entitlement {$plan->name}: " . ($enabled ? 'ready' : 'missing'));
            $failed = $failed || ! $enabled;
        }

        $lifetimePlan = Plan::query()
            ->where('name', 'Lifetime Access')
            ->where('price', 5000)
            ->first();

        if ($lifetimePlan && Schema::hasTable('user_orders') && Schema::hasTable('subscriptions')) {
            $missingLifetimeSubscriptions = DB::table('user_orders')
                ->where('plan_id', $lifetimePlan->id)
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

            $this->{$missingLifetimeSubscriptions === 0 ? 'info' : 'error'}("lifetime subscription repairs pending: {$missingLifetimeSubscriptions}");
            $failed = $failed || $missingLifetimeSubscriptions > 0;
        }

        foreach (Gateways::query()->where('is_active', 1)->orderBy('code')->get() as $gateway) {
            $complete = GatewayProducts::query()
                ->where('gateway_code', $gateway->code)
                ->whereNotNull('product_id')
                ->whereNotNull('price_id')
                ->whereHas('plan', static fn ($query) => $query->where('active', 1))
                ->count();

            $activePlans = $subscriptionPlans + $prepaidPlans;
            $this->line("gateway {$gateway->code}: complete={$complete}; active_plans={$activePlans}");
        }

        $duplicateMappings = GatewayProducts::query()
            ->selectRaw('plan_id, gateway_code, COUNT(*) AS aggregate')
            ->groupBy('plan_id', 'gateway_code')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->{$duplicateMappings === 0 ? 'info' : 'warn'}("duplicate gateway mappings: {$duplicateMappings}");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

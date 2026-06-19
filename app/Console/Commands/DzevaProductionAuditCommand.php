<?php

namespace App\Console\Commands;

use App\Models\GatewayProducts;
use App\Models\Gateways;
use App\Models\Plan;
use Illuminate\Console\Command;
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
        ] as $route) {
            $exists = Route::has($route);
            $this->{$exists ? 'info' : 'error'}("route {$route}: " . ($exists ? 'ready' : 'missing'));
            $failed = $failed || ! $exists;
        }

        $this->line('Amamihe credential: ' . (filled(setting('gemini_api_secret')) ? 'configured' : 'missing'));
        $this->line('Hikima credential: ' . (filled(setting('anthropic_api_secret')) ? 'configured' : 'missing'));

        $subscriptionPlans = Plan::query()->where('active', 1)->where('type', 'subscription')->count();
        $prepaidPlans = Plan::query()->where('active', 1)->where('type', 'prepaid')->count();
        $this->info("active plans: subscription={$subscriptionPlans}; prepaid={$prepaidPlans}");

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

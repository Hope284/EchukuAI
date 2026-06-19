<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'gatewayproducts_plan_gateway_unique';

    public function up(): void
    {
        if (! Schema::hasTable('gatewayproducts')) {
            return;
        }

        DB::table('gatewayproducts')
            ->select('plan_id', 'gateway_code')
            ->whereNotNull('gateway_code')
            ->groupBy('plan_id', 'gateway_code')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('plan_id')
            ->get()
            ->each(function (object $group): void {
                $mappings = DB::table('gatewayproducts')
                    ->where('plan_id', $group->plan_id)
                    ->where('gateway_code', $group->gateway_code)
                    ->orderByRaw('CASE WHEN product_id IS NOT NULL AND price_id IS NOT NULL THEN 0 ELSE 1 END')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                $keeper = $mappings->first();

                if (! $keeper) {
                    return;
                }

                $productId = $mappings->first(fn (object $mapping): bool => filled($mapping->product_id))?->product_id;
                $priceId = $mappings->first(fn (object $mapping): bool => filled($mapping->price_id))?->price_id;
                $payload = $mappings->first(fn (object $mapping): bool => filled($mapping->payload ?? null))?->payload;

                DB::table('gatewayproducts')->where('id', $keeper->id)->update([
                    'product_id' => $productId,
                    'price_id' => $priceId,
                    'payload' => $payload,
                    'updated_at' => now(),
                ]);

                DB::table('gatewayproducts')
                    ->where('plan_id', $group->plan_id)
                    ->where('gateway_code', $group->gateway_code)
                    ->where('id', '<>', $keeper->id)
                    ->delete();
            });

        Schema::table('gatewayproducts', function (Blueprint $table): void {
            $table->unique(['plan_id', 'gateway_code'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gatewayproducts')) {
            return;
        }

        Schema::table('gatewayproducts', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX);
        });
    }
};

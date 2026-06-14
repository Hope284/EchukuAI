<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('country');
            $table->string('state')->nullable();
            $table->string('company_name')->nullable();
            $table->string('referral_code')->unique();
            $table->string('status')->default('pending');
            $table->decimal('commission_rate', 8, 4)->default(20.0000);
            $table->string('preferred_payout_method')->nullable();
            $table->text('payout_details')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('parent_affiliate_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_affiliate_id')->constrained('parent_affiliates')->cascadeOnDelete();
            $table->foreignId('child_affiliate_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('child_affiliate_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['parent_affiliate_id', 'child_affiliate_user_id'], 'parent_affiliate_child_unique');
        });

        Schema::create('parent_affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_affiliate_id')->constrained('parent_affiliates')->cascadeOnDelete();
            $table->foreignId('child_affiliate_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_order_id')->constrained('user_orders')->cascadeOnDelete();
            $table->decimal('child_commission_amount', 12, 2)->default(0);
            $table->decimal('commission_rate', 8, 4)->default(20.0000);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 12)->default('USD');
            $table->string('status')->default('confirmed');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('user_order_id', 'parent_affiliate_commissions_order_unique');
        });

        Schema::create('parent_affiliate_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_affiliate_id')->constrained('parent_affiliates')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 12)->default('USD');
            $table->string('payout_method')->nullable();
            $table->text('payout_details_snapshot')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('parent_affiliate_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_affiliate_id')->constrained('parent_affiliates')->cascadeOnDelete();
            $table->string('gateway_key');
            $table->boolean('is_enabled')->default(true);
            $table->string('currency', 12)->nullable();
            $table->string('country')->default('*');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['parent_affiliate_id', 'gateway_key', 'country'], 'parent_affiliate_gateway_country_unique');
        });

        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 12);
            $table->string('target_currency', 12);
            $table->decimal('rate', 18, 8);
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['base_currency', 'target_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
        Schema::dropIfExists('parent_affiliate_payment_gateways');
        Schema::dropIfExists('parent_affiliate_withdrawals');
        Schema::dropIfExists('parent_affiliate_commissions');
        Schema::dropIfExists('parent_affiliate_children');
        Schema::dropIfExists('parent_affiliates');
    }
};

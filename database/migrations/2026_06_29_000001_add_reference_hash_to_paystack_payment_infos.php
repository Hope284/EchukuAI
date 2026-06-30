<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'paystack_reference_hash_unique';

    public function up(): void
    {
        if (! Schema::hasTable('paystack_payment_infos')
            || Schema::hasColumn('paystack_payment_infos', 'reference_hash')) {
            return;
        }

        Schema::table('paystack_payment_infos', function (Blueprint $table): void {
            $table->char('reference_hash', 64)->nullable()->after('reference');
            $table->unique('reference_hash', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('paystack_payment_infos')
            || ! Schema::hasColumn('paystack_payment_infos', 'reference_hash')) {
            return;
        }

        Schema::table('paystack_payment_infos', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX);
            $table->dropColumn('reference_hash');
        });
    }
};

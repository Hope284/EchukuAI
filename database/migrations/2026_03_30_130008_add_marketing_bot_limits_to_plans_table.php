<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('plans', 'marketing_bot_limits')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->json('marketing_bot_limits')->nullable()->after('blogpilot_limits');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('plans', 'marketing_bot_limits')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('marketing_bot_limits');
        });
    }
};

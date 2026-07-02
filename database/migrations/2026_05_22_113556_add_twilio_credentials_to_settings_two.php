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
        Schema::table('settings_two', function (Blueprint $table) {
            if (! Schema::hasColumn('settings_two', 'twilio_account_sid')) {
                $table->string('twilio_account_sid')->nullable()->after('elevenlabs_api_key');
            }
            if (! Schema::hasColumn('settings_two', 'twilio_auth_token')) {
                $table->string('twilio_auth_token')->nullable()->after('twilio_account_sid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings_two', function (Blueprint $table) {
            if (Schema::hasColumn('settings_two', 'twilio_auth_token')) {
                $table->dropColumn('twilio_auth_token');
            }
            if (Schema::hasColumn('settings_two', 'twilio_account_sid')) {
                $table->dropColumn('twilio_account_sid');
            }
        });
    }
};

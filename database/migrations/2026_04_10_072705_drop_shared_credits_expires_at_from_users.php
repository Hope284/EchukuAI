<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve existing expiry data. Version 10.8.1 no longer uses this
        // nullable column, but retaining it is backward compatible.
    }

    public function down(): void
    {
        //
    }
};

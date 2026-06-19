<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (range(1, 5) as $n) {
            DB::table('ext_ai_agent_avatars')->updateOrInsert(
                [
                    'user_id' => null,
                    'avatar'  => "vendor/ai-agent/images/agents/agent-{$n}.png",
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('ext_ai_agent_avatars')
            ->whereNull('user_id')
            ->where('avatar', 'like', 'vendor/ai-agent/images/agents/agent-%.png')
            ->delete();
    }
};

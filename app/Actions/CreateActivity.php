<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\Common\MenuService;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateActivity
{
    public static function for(
        User|int|string $userOrId,
        string $activity_type,
        string $activity_title,
        ?string $url = null
    ): void {
        $user = $userOrId instanceof User ? $userOrId : User::findOrFail($userOrId);

        try {
            $user->activities()->create([
                'activity_type'  => $activity_type,
                'activity_title' => $activity_title,
                'url'            => $url,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Activity logging failed', [
                'user_id' => $user->id,
                'activity_type' => $activity_type,
                'activity_title' => $activity_title,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            app(MenuService::class)->regenerate();
        } catch (Throwable $exception) {
            Log::warning('Menu regeneration after activity failed', [
                'user_id' => $user->id,
                'activity_type' => $activity_type,
                'activity_title' => $activity_title,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            Notify::toMany(
                User::admins()->get(),
                $activity_type . ' "' . $activity_title . '"',
                $user?->fullName(),
                $url
            );
        } catch (Throwable $exception) {
            Log::warning('Activity notification failed', [
                'user_id' => $user->id,
                'activity_type' => $activity_type,
                'activity_title' => $activity_title,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

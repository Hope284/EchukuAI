<?php

namespace App\Extensions\SocialMedia\System\Console\Commands;

use App\Extensions\SocialMedia\System\Enums\StatusEnum;
use App\Extensions\SocialMedia\System\Models\SocialMediaPost;
use App\Extensions\SocialMedia\System\Services\Publisher\Contracts\BasePublisherService;
use App\Extensions\SocialMedia\System\Services\Publisher\PublisherDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublishedCommand extends Command
{
    protected $signature = 'app:social-media-published-command';

    protected $description = 'Publish scheduled social media posts';

    public function handle(): int
    {
        $posts = SocialMediaPost::query()
            ->with('platform')
            ->where('status', StatusEnum::scheduled->value)
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->get();

        $service = app(PublisherDriver::class);

        foreach ($posts as $post) {
            $claimed = SocialMediaPost::query()
                ->whereKey($post->id)
                ->where('status', StatusEnum::scheduled->value)
                ->update(['status' => StatusEnum::pending->value]);

            if ($claimed !== 1) {
                continue;
            }

            $post->status = StatusEnum::pending;

            try {
                $driver = $service
                    ->setPost($post)
                    ->getDriver();

                if (! $driver instanceof BasePublisherService) {
                    $post->update(['status' => StatusEnum::failed->value]);
                    $post->agentPostFailed('This connected platform does not support publishing.');

                    continue;
                }

                $driver->publish();
            } catch (Throwable $throwable) {
                $post->update(['status' => StatusEnum::failed->value]);
                $post->agentPostFailed('Publishing failed. Reconnect the platform and try again.');
                Log::error('Scheduled social media publishing failed.', [
                    'post_id'     => $post->id,
                    'platform_id' => $post->social_media_platform_id,
                    'error'       => $throwable->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}

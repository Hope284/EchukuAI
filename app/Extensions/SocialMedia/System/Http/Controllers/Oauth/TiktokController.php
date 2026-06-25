<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers\Oauth;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Tiktok;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\Traits\HasBackRoute;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TiktokController extends Controller
{
    use HasBackRoute;

    public function __construct(public Tiktok $api) {}

    private function cacheKey(): string
    {
        return 'platforms.' . Auth::id() . '.tiktok';
    }

    public function redirect(Request $request)
    {
        if (Helper::appIsDemo()) {
            return back()->with([
                'type'    => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $this->setBackCacheRoute();

        if (! $this->api->configured()) {
            return $this->redirectToPlatforms('error', 'TikTok connection is not configured yet. Please contact an administrator.');
        }

        if ($request->has('platform_id') && $request->get('platform_id')) {
            Cache::remember($this->cacheKey(), 60, function () use ($request) {
                return $request->get('platform_id');
            });
        }

        return $this->api::authRedirect();
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return $this->redirectToPlatforms('error', $request->get('error_description', 'TikTok connection was cancelled or denied.'));
        }

        $expectedState = session('social_media_tiktok_oauth_state');
        if ($expectedState && ! hash_equals($expectedState, (string) $request->get('state'))) {
            session()->forget('social_media_tiktok_oauth_state');

            return $this->redirectToPlatforms('error', 'TikTok connection could not be verified. Please try again.');
        }
        session()->forget('social_media_tiktok_oauth_state');

        $code = $request->get('code');

        if (! $code) {
            return $this->redirectToPlatforms('error', 'Something went wrong, please try again.');
        }

        try {
            $response = $this->api->getAccessToken($code);
        } catch (Throwable $e) {
            Log::warning('TikTok OAuth token request failed', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);

            return $this->redirectToPlatforms('error', 'TikTok connection failed. Please try again.');
        }

        if ($response->failed() || $response->json('error')) {
            Log::warning('TikTok OAuth returned an error response', [
                'user_id' => Auth::id(),
                'status'  => $response->status(),
                'error'   => $response->json('error'),
            ]);

            return $this->redirectToPlatforms('error', 'TikTok connection failed. Please verify the app settings and try again.');
        }

        $tokenData = $response->object();

        $platformId = Cache::get($this->cacheKey());

        if ($platformId && is_numeric($platformId)) {

            $item = SocialMediaPlatform::query()
                ->where('user_id', Auth::id())
                ->where('platform', PlatformEnum::tiktok->value)
                ->where('id', $platformId)
                ->first();

            if ($item) {
                $item->update([
                    'credentials' => [
                        'platform_id'            => $tokenData?->open_id,
                        'access_token'           => $tokenData?->access_token ?? '',
                        'access_token_expire_at' => now()->addSeconds($tokenData?->expires_in ?? 0),

                        'refresh_token'           => $tokenData?->refresh_token ?? '',
                        'refresh_token_expire_at' => now()->addSeconds($tokenData?->refresh_expires_in ?? 0),
                    ],
                    'connected_at' => now(),
                    'expires_at'   => now()->addSeconds($tokenData?->expires_in ?? 0),
                ]);

                $this->api->setToken($tokenData?->access_token);

                $this->setProfileInfo($item);
            }

            Cache::forget($this->cacheKey());
        } else {
            $item = SocialMediaPlatform::query()->create([
                'user_id'     => Auth::id(),
                'platform'    => PlatformEnum::tiktok->value,
                'credentials' => [
                    'platform_id'            => $tokenData?->open_id,
                    'access_token'           => $tokenData?->access_token ?? '',
                    'access_token_expire_at' => now()->addSeconds($tokenData?->expires_in ?? 0),

                    'refresh_token'           => $tokenData?->refresh_token ?? '',
                    'refresh_token_expire_at' => now()->addSeconds($tokenData?->refresh_expires_in ?? 0),
                ],
                'connected_at' => now(),
                'expires_at'   => now()->addSeconds($tokenData?->expires_in ?? 0),
            ]);

            $this->api->setToken($tokenData?->access_token);

            $this->setProfileInfo($item);
        }

        return $this->redirectToPlatforms('success', 'Tiktok account connected successfully.');
    }

    protected function setProfileInfo(SocialMediaPlatform|Model|Builder $item): void
    {
        //        $userData = $this->api->getAccountInfo([
        //            'open_id',
        //        ])
        //            ->throw()
        //            ->json('data.user');

        $creatorInfoData = $this->api->getCreatorInfo();

        $creatorInfo = [];

        if (isset($creatorInfoData['error']['code']) && $creatorInfoData['error']['code'] === 'ok') {
            $creatorInfo = $creatorInfoData['data'] ?? [];
        }

        $followersCount = (int) (
            data_get($creatorInfo, 'follower_count')
            ?? data_get($creatorInfo, 'followers_count')
            ?? data_get($creatorInfo, 'fan_count')
            ?? data_get($creatorInfo, 'fans_count')
            ?? 0
        );

        if ($followersCount === 0) {
            $accountInfo = $this->api->getAccountInfo([
                'open_id',
                'follower_count',
                'followers_count',
                'fan_count',
            ])->json('data.user', []);

            $followersCount = (int) (
                data_get($accountInfo, 'follower_count')
                ?? data_get($accountInfo, 'followers_count')
                ?? data_get($accountInfo, 'fan_count')
                ?? data_get($accountInfo, 'fans_count')
                ?? 0
            );
        }

        $item->update([
            'credentials' => array_merge($item->credentials, [
                'name'     => $creatorInfo['creator_nickname'] ?? '',
                'username' => $creatorInfo['creator_username'] ?? '',
                'picture'  => $creatorInfo['creator_avatar_url'] ?? '',
                'meta'     => $creatorInfo ?? [],
            ]),
            'followers_count' => $followersCount,
        ]);
    }

    public function redirectToPlatforms(string $type = 'success', string $message = 'Tiktok account connected successfully.'): RedirectResponse
    {
        return to_route($this->getBackCacheRoute())->with([
            'type'    => $type,
            'message' => trans($message),
        ]);
    }

    public function verify()
    {
        return setting('TIKTOK_OAUTH_VERIFY', 'tiktok-developers-site-verification=U4IyiClYTw8yPBShtWnQkY01ncYucsC3');
    }
}

<?php

namespace App\Extensions\OpenAIRealtimeChat\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity as EntityFacade;
use App\Helpers\Classes\ApiHelper;
use App\Helpers\Classes\Helper;
use App\Helpers\Classes\RateLimiter\RateLimiter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RealtimeVoiceChatController extends Controller
{
    private const DEMO_MAX_ATTEMPTS = 4;

    private const DEMO_RATE_LIMIT_KEY = 'voice-chat-attempts';

    public function checkBalance(?bool $onStart = false): JsonResponse
    {
        if (Helper::appIsDemo()) {

            $clientIp = Helper::getRequestIp();
            $rateLimiter = new RateLimiter(self::DEMO_RATE_LIMIT_KEY, self::DEMO_MAX_ATTEMPTS);

            if ($rateLimiter->attempt($clientIp)) {
                return response()->json(['status' => 'success', 'message' => 'Demo mode'], 200);

            }

            return response()->json(['status' => 'error', 'message' => 'Exceeded messages limit on demo'], 200);
        }

        $driver = EntityFacade::driver(EntityEnum::GPT_4_O_REALTIME_PREVIEW);

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status'  => 'error',
            ], 200);
        }

        return response()->json(['status' => 'success', 'message' => ''], 200);
    }

    /**
     * Generate an ephemeral OpenAI Realtime session token.
     */
    public function session(): JsonResponse
    {
        if (Helper::appIsDemo()) {
            $rateLimiter = new RateLimiter(self::DEMO_RATE_LIMIT_KEY, self::DEMO_MAX_ATTEMPTS);

            if ($rateLimiter->remainingAttempts(Helper::getRequestIp()) <= 0) {
                return response()->json([
                    'error' => __('Exceeded messages limit on demo'),
                ], 429);
            }
        }

        $apiKey = ApiHelper::setOpenAiKey();
        $model = 'gpt-4o-realtime-preview-2024-12-17';

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/realtime/sessions', [
                'model'                     => $model,
                'voice'                     => 'verse',
                'input_audio_transcription' => [
                    'model' => 'gpt-4o-mini-transcribe',
                ],
                'turn_detection' => [
                    'type'                => 'server_vad',
                    'silence_duration_ms' => 500,
                ],
            ]);

        if ($response->failed()) {
            Log::error('Failed to create OpenAI ephemeral token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to create voice chat session.',
            ], 500);
        }

        $data = $response->json();
        $ephemeralKey = $data['client_secret']['value'] ?? null;

        if (! $ephemeralKey) {
            Log::error('Ephemeral token not found in OpenAI response', [
                'body' => $response->body(),
            ]);

            return response()->json([
                'error' => 'Failed to create voice chat session.',
            ], 500);
        }

        return response()->json([
            'ephemeral_key' => $ephemeralKey,
            'model'         => $model,
        ]);
    }
}

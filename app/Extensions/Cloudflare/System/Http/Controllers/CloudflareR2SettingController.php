<?php

namespace App\Extensions\Cloudflare\System\Http\Controllers;

use App\Extensions\Cloudflare\System\Http\Requests\CloudflareR2Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CloudflareR2SettingController extends Controller
{
    public function index(): View
    {
        $disk = config('filesystems.disks.r2', []);
        $requiredKeys = [
            'key',
            'secret',
            'region',
            'bucket',
            'endpoint',
            'url',
        ];

        $missingKeys = collect($requiredKeys)
            ->filter(static fn (string $key): bool => blank($disk[$key] ?? null))
            ->values();

        return view('cloudflare::settings', [
            'missingKeys'  => $missingKeys,
            'isConfigured' => $missingKeys->isEmpty(),
        ]);
    }

    public function update(CloudflareR2Request $request): ?RedirectResponse
    {
        $data = $request->validated();
        $data['CLOUDFLARE_R2_URL'] = $data['CLOUDFLARE_R2_URL'] ?: $data['CLOUDFLARE_R2_ENDPOINT'];

        try {
            \App\Helpers\Classes\Helper::setEnv($data);

            return redirect()->back()->with([
                'message' => __('Settings updated successfully.'),
                'type'    => 'success',
            ]);
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->withInput()->with([
                'message' => __('Cloudflare R2 settings could not be saved. Please verify the credentials and try again.'),
                'type'    => 'error',
            ]);
        }
    }
}

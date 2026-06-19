<?php

namespace App\Extensions\Cloudflare\System\Http\Controllers;

use App\Extensions\Cloudflare\System\Http\Requests\CloudflareR2Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Throwable;

class CloudflareR2SettingController extends Controller
{
    public function index()
    {
        return view('cloudflare::settings');
    }

    public function update(CloudflareR2Request $request): ?RedirectResponse
    {
        $data = $request->validated();

        $request['CLOUDFLARE_R2_URL'] = $request['CLOUDFLARE_R2_URL'] ?: $request['CLOUDFLARE_R2_ENDPOINT'];

        try {
            \App\Helpers\Classes\Helper::setEnv($data);

            return redirect()->back()->with([
                'message' => __('Settings updated successfully.'),
                'type'    => 'success',
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with([
                'message' => __('Cloud storage settings could not be saved. Please verify the values and try again.'),
                'type'    => 'error',
            ]);
        }
    }
}

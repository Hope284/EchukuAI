<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Models\ParentAffiliate;

class StrategicPartnerReferralController extends Controller
{
    public function __invoke(?string $code = null): RedirectResponse
    {
        if (! $code) {
            return redirect()->route('register')
                ->with(['message' => __('Please use a valid Strategic Partner link.'), 'type' => 'error']);
        }

        $partner = ParentAffiliate::query()
            ->where('referral_code', $code)
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->first();

        if (! $partner) {
            return redirect()->route('register')
                ->with(['message' => __('This Strategic Partner link is not active. You can still create your Dzeva account.'), 'type' => 'error']);
        }

        return redirect()->route('register', ['partner' => $partner->referral_code]);
    }
}

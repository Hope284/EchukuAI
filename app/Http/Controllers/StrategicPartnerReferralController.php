<?php

namespace App\Http\Controllers;

use App\Models\ParentAffiliate;
use Illuminate\Http\RedirectResponse;

class StrategicPartnerReferralController extends Controller
{
    public function __invoke(string $code): RedirectResponse
    {
        $partner = ParentAffiliate::query()
            ->where('referral_code', $code)
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->firstOrFail();

        return redirect()->route('register', ['partner' => $partner->referral_code]);
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ParentAffiliate;
use App\Models\ParentAffiliateCommission;
use App\Models\ParentAffiliateWithdrawal;
use App\Services\StrategicPartner\StrategicPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StrategicPartnerController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $this->partner($request);

        return $this->dashboardView($partner, 'overview');
    }

    public function children(Request $request): View
    {
        return $this->dashboardView($this->partner($request), 'children');
    }

    public function earnings(Request $request): View
    {
        return $this->dashboardView($this->partner($request), 'earnings');
    }

    public function withdrawals(Request $request): View
    {
        return $this->dashboardView($this->partner($request), 'withdrawals');
    }

    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        try {
            StrategicPartnerService::requestWithdrawal($partner, (float) $validated['amount']);
        } catch (\InvalidArgumentException $exception) {
            return back()->with(['message' => $exception->getMessage(), 'type' => 'error']);
        }

        return back()->with(['message' => __('Strategic Partner withdrawal request submitted.'), 'type' => 'success']);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $partner = $this->partner($request);

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'state' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'preferred_payout_method' => ['nullable', 'string', 'max:255'],
            'payout_account_name' => ['nullable', 'string', 'max:255'],
            'payout_account_number' => ['nullable', 'string', 'max:255'],
            'payout_bank_name' => ['nullable', 'string', 'max:255'],
            'payout_extra' => ['nullable', 'string', 'max:1000'],
        ]);

        $partner->update([
            'phone' => $validated['phone'] ?? null,
            'state' => $validated['state'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'preferred_payout_method' => $validated['preferred_payout_method'] ?? null,
            'payout_details' => [
                'account_name' => $validated['payout_account_name'] ?? null,
                'account_number' => $validated['payout_account_number'] ?? null,
                'bank_name' => $validated['payout_bank_name'] ?? null,
                'extra' => $validated['payout_extra'] ?? null,
            ],
        ]);

        return back()->with(['message' => __('Strategic Partner profile updated.'), 'type' => 'success']);
    }

    private function dashboardView(ParentAffiliate $partner, string $tab): View
    {
        $children = StrategicPartnerService::childSummaries($partner);
        $withdrawals = $partner->withdrawals()->latest()->get();
        $commissions = ParentAffiliateCommission::query()
            ->where('parent_affiliate_id', $partner->id)
            ->latest()
            ->limit(25)
            ->get();

        $totalEarned = (float) $partner->commissions()->where('status', 'confirmed')->sum('amount');
        $pendingWithdrawals = (float) $partner->withdrawals()->whereIn('status', [
            ParentAffiliateWithdrawal::STATUS_PENDING,
            ParentAffiliateWithdrawal::STATUS_APPROVED,
        ])->sum('amount');
        $paidWithdrawals = (float) $partner->withdrawals()->where('status', ParentAffiliateWithdrawal::STATUS_PAID)->sum('amount');
        $totalUsersUnderChildren = $children->sum('referred_users_count');

        return view('panel.user.strategic-partner.index', [
            'partner' => $partner,
            'tab' => $tab,
            'referralUrl' => StrategicPartnerService::referralUrl($partner),
            'children' => $children,
            'commissions' => $commissions,
            'withdrawals' => $withdrawals,
            'totalEarned' => $totalEarned,
            'pendingWithdrawals' => $pendingWithdrawals,
            'paidWithdrawals' => $paidWithdrawals,
            'availableBalance' => StrategicPartnerService::availableBalance($partner),
            'totalUsersUnderChildren' => $totalUsersUnderChildren,
        ]);
    }

    private function partner(Request $request): ParentAffiliate
    {
        $partner = $request->attributes->get('strategicPartner');

        if ($partner instanceof ParentAffiliate) {
            return $partner;
        }

        return ParentAffiliate::query()
            ->where('user_id', $request->user()->id)
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->firstOrFail();
    }
}

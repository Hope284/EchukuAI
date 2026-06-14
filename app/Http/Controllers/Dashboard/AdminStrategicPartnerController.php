<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Gateways;
use App\Models\ParentAffiliate;
use App\Models\ParentAffiliatePaymentGateway;
use App\Models\ParentAffiliateWithdrawal;
use App\Models\User;
use App\Services\StrategicPartner\StrategicPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminStrategicPartnerController extends Controller
{
    public function index(): View
    {
        $partners = ParentAffiliate::query()
            ->with('user')
            ->withCount('children')
            ->latest()
            ->paginate(25);

        return view('panel.admin.strategic-partners.index', compact('partners'));
    }

    public function create(): View
    {
        return view('panel.admin.strategic-partners.form', [
            'partner' => new ParentAffiliate([
                'status' => ParentAffiliate::STATUS_APPROVED,
                'commission_rate' => 20,
            ]),
            'users' => User::query()->orderBy('email')->limit(500)->get(['id', 'name', 'surname', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePartner($request);
        $attributes = $this->partnerAttributes($validated);

        $partner = DB::transaction(function () use ($validated, $attributes, $request) {
            return ParentAffiliate::query()->create([
                ...$attributes,
                'created_by_admin_id' => $request->user()->id,
                'referral_code' => StrategicPartnerService::uniqueReferralCode(),
                'approved_at' => ($validated['status'] ?? ParentAffiliate::STATUS_PENDING) === ParentAffiliate::STATUS_APPROVED ? now() : null,
                'payout_details' => $this->payoutDetails($validated),
            ]);
        });

        return redirect()
            ->route('dashboard.admin.strategic-partners.show', $partner)
            ->with(['message' => __('Strategic Partner created.'), 'type' => 'success']);
    }

    public function show(ParentAffiliate $strategicPartner): View
    {
        return view('panel.admin.strategic-partners.show', [
            'partner' => $strategicPartner->load('user', 'paymentGateways'),
            'children' => StrategicPartnerService::childSummaries($strategicPartner),
            'withdrawals' => $strategicPartner->withdrawals()->latest()->get(),
            'gateways' => Gateways::query()->where('is_active', 1)->orderBy('title')->get(),
            'referralUrl' => StrategicPartnerService::referralUrl($strategicPartner),
            'availableBalance' => StrategicPartnerService::availableBalance($strategicPartner),
        ]);
    }

    public function edit(ParentAffiliate $strategicPartner): View
    {
        return view('panel.admin.strategic-partners.form', [
            'partner' => $strategicPartner,
            'users' => User::query()->orderBy('email')->limit(500)->get(['id', 'name', 'surname', 'email']),
        ]);
    }

    public function update(Request $request, ParentAffiliate $strategicPartner): RedirectResponse
    {
        $validated = $this->validatePartner($request, $strategicPartner);
        $attributes = $this->partnerAttributes($validated);
        $wasApproved = $strategicPartner->status === ParentAffiliate::STATUS_APPROVED;

        $strategicPartner->update([
            ...$attributes,
            'approved_at' => ! $wasApproved && ($validated['status'] ?? null) === ParentAffiliate::STATUS_APPROVED ? now() : $strategicPartner->approved_at,
            'payout_details' => $this->payoutDetails($validated),
        ]);

        return redirect()
            ->route('dashboard.admin.strategic-partners.show', $strategicPartner)
            ->with(['message' => __('Strategic Partner updated.'), 'type' => 'success']);
    }

    public function saveGateways(Request $request, ParentAffiliate $strategicPartner): RedirectResponse
    {
        $validated = $request->validate([
            'gateways' => ['array'],
            'gateways.*.gateway_key' => ['required', 'string', 'max:255'],
            'gateways.*.is_enabled' => ['nullable', 'boolean'],
            'gateways.*.country' => ['nullable', 'string', 'max:255'],
            'gateways.*.currency' => ['nullable', 'string', 'max:12'],
        ]);

        foreach ($validated['gateways'] ?? [] as $rule) {
            ParentAffiliatePaymentGateway::query()->updateOrCreate(
                [
                    'parent_affiliate_id' => $strategicPartner->id,
                    'gateway_key' => $rule['gateway_key'],
                    'country' => filled($rule['country'] ?? null) ? $rule['country'] : '*',
                ],
                [
                    'is_enabled' => (bool) ($rule['is_enabled'] ?? false),
                    'currency' => $rule['currency'] ?? null,
                ]
            );
        }

        return back()->with(['message' => __('Strategic Partner payment gateway rules saved.'), 'type' => 'success']);
    }

    public function updateWithdrawal(Request $request, ParentAffiliateWithdrawal $withdrawal): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                ParentAffiliateWithdrawal::STATUS_APPROVED,
                ParentAffiliateWithdrawal::STATUS_PAID,
                ParentAffiliateWithdrawal::STATUS_REJECTED,
            ])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $withdrawal->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'processed_at' => now(),
        ]);

        return back()->with(['message' => __('Strategic Partner withdrawal updated.'), 'type' => 'success']);
    }

    private function validatePartner(Request $request, ?ParentAffiliate $partner = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('parent_affiliates', 'email')->ignore($partner?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([ParentAffiliate::STATUS_PENDING, ParentAffiliate::STATUS_APPROVED, ParentAffiliate::STATUS_SUSPENDED])],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'preferred_payout_method' => ['nullable', 'string', 'max:255'],
            'payout_account_name' => ['nullable', 'string', 'max:255'],
            'payout_account_number' => ['nullable', 'string', 'max:255'],
            'payout_bank_name' => ['nullable', 'string', 'max:255'],
            'payout_extra' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function payoutDetails(array $validated): array
    {
        return [
            'account_name' => $validated['payout_account_name'] ?? null,
            'account_number' => $validated['payout_account_number'] ?? null,
            'bank_name' => $validated['payout_bank_name'] ?? null,
            'extra' => $validated['payout_extra'] ?? null,
        ];
    }

    private function partnerAttributes(array $validated): array
    {
        return collect($validated)
            ->only([
                'user_id',
                'name',
                'email',
                'phone',
                'country',
                'state',
                'company_name',
                'status',
                'commission_rate',
                'preferred_payout_method',
            ])
            ->all();
    }
}

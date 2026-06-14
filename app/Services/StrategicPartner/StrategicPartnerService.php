<?php

namespace App\Services\StrategicPartner;

use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Models\Gateways;
use App\Models\ParentAffiliate;
use App\Models\ParentAffiliateChild;
use App\Models\ParentAffiliateCommission;
use App\Models\ParentAffiliatePaymentGateway;
use App\Models\ParentAffiliateWithdrawal;
use App\Models\User;
use App\Models\UserOrder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StrategicPartnerService
{
    public static function referralUrl(ParentAffiliate $partner): string
    {
        return url('/partner/' . $partner->referral_code);
    }

    public static function uniqueReferralCode(): string
    {
        do {
            $code = 'SP-' . Str::upper(Str::random(10));
        } while (ParentAffiliate::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public static function approvedPartnerForUser(?User $user): ?ParentAffiliate
    {
        if (! $user) {
            return null;
        }

        return ParentAffiliate::query()
            ->where('user_id', $user->id)
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->first();
    }

    public static function partnerForCheckoutUser(?User $user): ?ParentAffiliate
    {
        if (! $user) {
            return null;
        }

        if ($partner = self::approvedPartnerForUser($user)) {
            return $partner;
        }

        $childUserId = $user->affiliate_id ?: $user->id;

        return ParentAffiliateChild::query()
            ->with('parentAffiliate')
            ->where('child_affiliate_user_id', $childUserId)
            ->where('status', 'active')
            ->whereHas('parentAffiliate', static function ($query) {
                $query->where('status', ParentAffiliate::STATUS_APPROVED);
            })
            ->first()
            ?->parentAffiliate;
    }

    public static function linkChildAffiliate(ParentAffiliate $partner, User $child): ParentAffiliateChild
    {
        return ParentAffiliateChild::query()->updateOrCreate(
            [
                'parent_affiliate_id' => $partner->id,
                'child_affiliate_user_id' => $child->id,
            ],
            [
                'child_affiliate_code' => $child->affiliate_code,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );
    }

    public static function createCommissionForOrder(UserOrder $order): ?ParentAffiliateCommission
    {
        $childCommission = (float) ($order->affiliate_earnings ?? 0);

        if ($childCommission <= 0 || ! self::isCommissionableStatus((string) $order->status)) {
            return null;
        }

        $orderUser = $order->user;
        $childAffiliateId = $orderUser?->affiliate_id;

        if (! $childAffiliateId) {
            return null;
        }

        $child = ParentAffiliateChild::query()
            ->with('parentAffiliate')
            ->where('child_affiliate_user_id', $childAffiliateId)
            ->where('status', 'active')
            ->whereHas('parentAffiliate', static function ($query) {
                $query->where('status', ParentAffiliate::STATUS_APPROVED);
            })
            ->first();

        if (! $child?->parentAffiliate) {
            return null;
        }

        $partner = $child->parentAffiliate;
        $rate = (float) $partner->commission_rate;
        $amount = round($childCommission * ($rate / 100), 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(static function () use ($partner, $childAffiliateId, $order, $childCommission, $rate, $amount) {
            return ParentAffiliateCommission::query()->updateOrCreate(
                ['user_order_id' => $order->id],
                [
                    'parent_affiliate_id' => $partner->id,
                    'child_affiliate_user_id' => $childAffiliateId,
                    'child_commission_amount' => $childCommission,
                    'commission_rate' => $rate,
                    'amount' => $amount,
                    'currency' => self::orderCurrencyCode($order),
                    'status' => 'confirmed',
                    'metadata' => [
                        'order_id' => $order->order_id,
                        'payment_type' => $order->payment_type,
                    ],
                ]
            );
        });
    }

    public static function availableBalance(ParentAffiliate $partner): float
    {
        $earned = (float) $partner->commissions()->where('status', 'confirmed')->sum('amount');
        $locked = (float) $partner->withdrawals()
            ->whereIn('status', [ParentAffiliateWithdrawal::STATUS_PENDING, ParentAffiliateWithdrawal::STATUS_APPROVED, ParentAffiliateWithdrawal::STATUS_PAID])
            ->sum('amount');

        return max(0, round($earned - $locked, 2));
    }

    public static function requestWithdrawal(ParentAffiliate $partner, float $amount): ParentAffiliateWithdrawal
    {
        if ($amount <= 0 || $amount > self::availableBalance($partner)) {
            throw new \InvalidArgumentException(__('Requested amount exceeds available Strategic Partner balance.'));
        }

        return DB::transaction(static function () use ($partner, $amount) {
            return ParentAffiliateWithdrawal::query()->create([
                'parent_affiliate_id' => $partner->id,
                'amount' => $amount,
                'currency' => currency()->code ?? 'USD',
                'payout_method' => $partner->preferred_payout_method,
                'payout_details_snapshot' => $partner->payout_details ?: [],
                'status' => ParentAffiliateWithdrawal::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        });
    }

    public static function availableGateways(?User $user, ?EloquentCollection $activeGateways = null): EloquentCollection
    {
        $activeGateways ??= Gateways::query()->where('is_active', 1)->get();
        $partner = self::partnerForCheckoutUser($user);

        if (! $partner) {
            return $activeGateways;
        }

        $rules = ParentAffiliatePaymentGateway::query()
            ->where('parent_affiliate_id', $partner->id)
            ->get();

        if ($rules->isEmpty()) {
            return $activeGateways;
        }

        $country = trim((string) ($user?->country ?: $partner->country));
        $allowed = $rules
            ->filter(static fn (ParentAffiliatePaymentGateway $rule): bool => $rule->is_enabled && in_array($rule->country, ['*', $country], true))
            ->pluck('gateway_key')
            ->unique()
            ->values();

        return $activeGateways
            ->filter(static fn (Gateways $gateway): bool => $allowed->contains($gateway->code))
            ->values();
    }

    public static function gatewayAllowed(?User $user, string $gatewayCode): bool
    {
        return self::availableGateways($user)
            ->pluck('code')
            ->contains($gatewayCode);
    }

    public static function localCurrencyQuote(float $baseAmount, ?User $user): ?array
    {
        $baseCurrency = currency()->code ?? 'USD';
        $targetCurrency = self::countryCurrencyCode($user?->country);

        if (! $targetCurrency || $targetCurrency === $baseCurrency) {
            return null;
        }

        $rate = CurrencyExchangeRate::query()
            ->where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            return null;
        }

        $currency = Currency::query()->where('code', $targetCurrency)->first();

        return [
            'base_currency' => $baseCurrency,
            'target_currency' => $targetCurrency,
            'symbol' => $currency?->symbol ?: $targetCurrency,
            'amount' => round($baseAmount * (float) $rate->rate, 2),
            'rate' => (float) $rate->rate,
        ];
    }

    public static function childSummaries(ParentAffiliate $partner): Collection
    {
        return $partner->children()
            ->with('childAffiliate')
            ->get()
            ->map(function (ParentAffiliateChild $child) use ($partner) {
                $childUser = $child->childAffiliate;
                $query = ParentAffiliateCommission::query()
                    ->where('parent_affiliate_id', $partner->id)
                    ->where('child_affiliate_user_id', $child->child_affiliate_user_id);

                return [
                    'child' => $child,
                    'user' => $childUser,
                    'last_30_days' => (float) (clone $query)->where('created_at', '>=', now()->subDays(30))->sum('amount'),
                    'total' => (float) (clone $query)->sum('amount'),
                    'referred_users_count' => User::query()->where('affiliate_id', $child->child_affiliate_user_id)->count(),
                ];
            });
    }

    private static function isCommissionableStatus(string $status): bool
    {
        $status = Str::lower($status);

        return str_contains($status, 'success') || str_contains($status, 'approved') || str_contains($status, 'paid');
    }

    private static function orderCurrencyCode(UserOrder $order): string
    {
        $gateway = Gateways::query()->where('code', $order->payment_type)->first();
        $currency = $gateway?->currency ? Currency::query()->find($gateway->currency) : null;

        return $currency?->code ?: (currency()->code ?? 'USD');
    }

    private static function countryCurrencyCode(?string $country): ?string
    {
        $country = Str::lower(trim((string) $country));

        return match ($country) {
            'nigeria', 'ng' => 'NGN',
            'ghana', 'gh' => 'GHS',
            'kenya', 'ke' => 'KES',
            'south africa', 'za' => 'ZAR',
            'uganda', 'ug' => 'UGX',
            'tanzania', 'tz' => 'TZS',
            'rwanda', 'rw' => 'RWF',
            default => null,
        };
    }
}

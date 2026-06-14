<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentAffiliate extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'created_by_admin_id',
        'name',
        'email',
        'phone',
        'country',
        'state',
        'company_name',
        'referral_code',
        'status',
        'commission_rate',
        'preferred_payout_method',
        'payout_details',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'commission_rate' => 'float',
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'payout_details' => 'encrypted:array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(ParentAffiliateChild::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ParentAffiliateCommission::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(ParentAffiliateWithdrawal::class);
    }

    public function paymentGateways(): HasMany
    {
        return $this->hasMany(ParentAffiliatePaymentGateway::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}

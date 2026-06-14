<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAffiliateWithdrawal extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'parent_affiliate_id',
        'amount',
        'currency',
        'payout_method',
        'payout_details_snapshot',
        'status',
        'admin_note',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'payout_details_snapshot' => 'encrypted:array',
    ];

    public function parentAffiliate(): BelongsTo
    {
        return $this->belongsTo(ParentAffiliate::class);
    }
}

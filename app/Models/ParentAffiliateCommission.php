<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAffiliateCommission extends Model
{
    protected $fillable = [
        'parent_affiliate_id',
        'child_affiliate_user_id',
        'user_order_id',
        'child_commission_amount',
        'commission_rate',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'child_commission_amount' => 'float',
        'commission_rate' => 'float',
        'amount' => 'float',
        'metadata' => 'array',
    ];

    public function parentAffiliate(): BelongsTo
    {
        return $this->belongsTo(ParentAffiliate::class);
    }

    public function childAffiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_affiliate_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(UserOrder::class, 'user_order_id');
    }
}

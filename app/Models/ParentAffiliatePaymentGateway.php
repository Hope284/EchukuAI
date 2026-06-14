<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAffiliatePaymentGateway extends Model
{
    protected $fillable = [
        'parent_affiliate_id',
        'gateway_key',
        'is_enabled',
        'currency',
        'country',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function parentAffiliate(): BelongsTo
    {
        return $this->belongsTo(ParentAffiliate::class);
    }
}

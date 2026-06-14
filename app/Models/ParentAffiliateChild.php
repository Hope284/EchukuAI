<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAffiliateChild extends Model
{
    protected $fillable = [
        'parent_affiliate_id',
        'child_affiliate_user_id',
        'child_affiliate_code',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function parentAffiliate(): BelongsTo
    {
        return $this->belongsTo(ParentAffiliate::class);
    }

    public function childAffiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_affiliate_user_id');
    }
}

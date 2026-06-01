<?php

namespace App\Extensions\Affilate\System\Listeners;

use App\Extensions\Affilate\System\Events\AffiliateEvent;
use App\Models\Currency;
use App\Models\UserAffiliate;

class AffiliateListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AffiliateEvent $event): void
    {
        $user = auth()->user();
        if ($user->affiliate_status == 1) {
            $currency = Currency::where('id', $event->currency)->first()->symbol;
            $model = UserAffiliate::updateOrCreate(['user_id' => $user->id],
                ['status' => 'success', 'currency' => $currency]);
            $model->amount += $event->total;
            $model->save();
        }
    }
}

<?php

namespace App\Extensions\Affilate\System\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AffiliateEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $total;

    public $currency;

    public function __construct($total, $currency)
    {
        $this->total = $total;
        $this->currency = $currency;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(1),
        ];
    }
}

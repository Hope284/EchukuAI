<?php

declare(strict_types=1);

namespace App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns;

use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use Illuminate\Support\Facades\Auth;

trait AuthorizesAgentOwnership
{
    public function authorize(): bool
    {
        $agentId = $this->input('id');

        return Auth::check()
            && is_numeric($agentId)
            && ExtPhoneCallAgent::query()
                ->whereKey((int) $agentId)
                ->where('user_id', Auth::id())
                ->exists();
    }
}

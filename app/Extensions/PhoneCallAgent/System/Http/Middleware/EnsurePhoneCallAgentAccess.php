<?php

declare(strict_types=1);

namespace App\Extensions\PhoneCallAgent\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneCallAgentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $plan = $user->activePlan();
        if (! $plan || ! $plan->checkOpenAiItem('ext_phone_call_agent')) {
            abort(403, __('Phone Call Agent is not available on your current plan.'));
        }

        return $next($request);
    }
}

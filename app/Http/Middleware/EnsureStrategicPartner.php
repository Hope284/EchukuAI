<?php

namespace App\Http\Middleware;

use App\Models\ParentAffiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStrategicPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            $partner = ParentAffiliate::query()
                ->where('user_id', $user->id)
                ->where('status', ParentAffiliate::STATUS_APPROVED)
                ->first();

            if ($partner) {
                $request->attributes->set('strategicPartner', $partner);

                return $next($request);
            }

            if ($request->routeIs('dashboard.user.strategic-partner.*')) {
                return redirect()->route('dashboard.admin.strategic-partners.index');
            }

            return $next($request);
        }

        $partner = ParentAffiliate::query()
            ->where('user_id', $user->id)
            ->where('status', ParentAffiliate::STATUS_APPROVED)
            ->first();

        abort_unless($partner, 403, __('Only approved Strategic Partners can access this area.'));

        $request->attributes->set('strategicPartner', $partner);

        return $next($request);
    }
}

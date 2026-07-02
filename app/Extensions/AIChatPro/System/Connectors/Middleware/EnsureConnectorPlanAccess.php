<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Connectors\Middleware;

use App\Extensions\AIChatPro\System\Connectors\ConnectorPlanGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConnectorPlanAccess
{
    public function handle(Request $request, Closure $next, string $connector): Response
    {
        abort_unless(ConnectorPlanGate::allows($request->user(), $connector), 404);

        return $next($request);
    }
}

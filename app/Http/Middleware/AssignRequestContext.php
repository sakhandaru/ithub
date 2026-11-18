<?php

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = Str::ulid()->toBase32();
        $timezone = $request->user()?->activeWorkspace()->value('timezone');

        RequestContext::assign(
            requestId: $requestId,
            actorId: $request->user()?->getAuthIdentifier(),
            timezone: is_string($timezone) ? $timezone : (string) config('sitop.default_timezone'),
            ipAddress: $request->ip(),
        );

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

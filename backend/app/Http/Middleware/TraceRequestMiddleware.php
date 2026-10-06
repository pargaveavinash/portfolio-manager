<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TraceRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID');

        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = Str::uuid()->toString();
        }

        // Bind to Laravel Context for logging and queues
        Context::add('request_id', $requestId);

        $response = $next($request);

        // Append to the outgoing response
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

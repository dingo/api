<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Http\Request;

class RouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return \Dingo\Api\Http\Response
     */
    public function handle($request, Closure $next)
    {
        if ($request instanceof Request) {
            dd('here');
        }

        return $next($request);
    }
}

<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Route;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\RateLimit\Handler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RateLimit
{
    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * Rate limit handler instance.
     *
     * @var \Dingo\Api\Http\RateLimit\Handler
     */
    protected $handler;

    /**
     * Create a new rate limit middleware instance.
     *
     * @param \Dingo\Api\Routing\Router         $router
     * @param \Dingo\Api\Http\RateLimit\Handler $handler
     *
     * @return void
     */
    public function __construct(Router $router, Handler $handler)
    {
        $this->router = $router;
        $this->handler = $handler;
    }

    /**
     * Perform rate limiting before a request is executed.
     *
     * @param \Dingo\Api\Http\Request $request
     * @param \Closure                $next
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if ($route->hasThrottle()) {
            $this->handler->setThrottle($route->getThrottle());
        }

        $this->handler->rateLimitRequest($request, $route->getRateLimit(), $route->getLimitExpiration());

        if ($this->handler->exceededRateLimit()) {
            return $this->responseWithHeaders(
                new Response('You have exceeded your rate limit.', 403)
            );
        }

        $response = $next($request);

        if ($this->handler->requestWasRateLimited()) {
            return $this->responseWithHeaders($response);
        }

        return $response;
    }

    /**
     * Send the response with the rate limit headers.
     *
     * @param \Dingo\Api\Http\Response $response
     *
     * @return \Dingo\Api\Http\Response
     */
    protected function responseWithHeaders($response)
    {
        $response->headers->set('X-RateLimit-Limit', $this->handler->getThrottleLimit());
        $response->headers->set('X-RateLimit-Remaining', $this->handler->getRemainingLimit());
        $response->headers->set('X-RateLimit-Reset', $this->handler->getRateLimitReset());

        return $response;
    }
}

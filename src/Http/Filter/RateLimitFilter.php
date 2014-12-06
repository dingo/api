<?php

namespace Dingo\Api\Http\Filter;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\RateLimit\RateLimiter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RateLimitFilter extends Filter
{
    /**
     * API router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * API rate limiter instance.
     *
     * @var \Dingo\Api\Http\RateLimit\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new rate limit filter instance.
     *
     * @param \Dingo\Api\Routing\Router             $router
     * @param \Dingo\Api\Http\RateLimit\RateLimiter $limiter
     *
     * @return void
     */
    public function __construct(Router $router, RateLimiter $limiter)
    {
        $this->router = $router;
        $this->limiter = $limiter;
    }

    /**
     * Perform rate limiting before a request is executed.
     *
     * @param \Dingo\Api\Routing\Route $route
     * @param \Illuminate\Http\Request $request
     * @param int                      $limit
     * @param int                      $expires
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     *
     * @return mixed
     */
    public function filter(Route $route, Request $request, $limit = 0, $expires = 0)
    {
        if ($this->requestIsInternal($request)) {
            return;
        }

        $limit = $route->getRateLimit($limit);
        $expires = $route->getLimitExpiration($expires);

        $this->limiter->rateLimitRequest($request, $limit, $expires);

        if (! $this->limiter->requestWasRateLimited()) {
            return;
        }

        $this->attachAfterFilter();

        if ($this->limiter->exceededRateLimit()) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * Attach the after filter to adjust the response.
     *
     * @return void
     */
    protected function attachAfterFilter()
    {
        $this->router->after(function (Request $request, Response $response) {
            $response->headers->set('X-RateLimit-Limit', $this->limiter->getThrottle()->getLimit());
            $response->headers->set('X-RateLimit-Remaining', $this->limiter->getRemainingLimit());
            $response->headers->set('X-RateLimit-Reset', $this->limiter->getRateLimitReset());
        });
    }
}

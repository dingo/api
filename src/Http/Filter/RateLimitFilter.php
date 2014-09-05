<?php

namespace Dingo\Api\Http\Filter;

use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Authenticator;
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
     * API authenticator instance.
     * 
     * @var \Dingo\Api\Auth\Authenticator
     */
    protected $auth;

    /**
     * API rate limiter instance.
     * 
     * @var \Dingo\Api\Http\RateLimit\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new rate limit filter instance.
     * 
     * @param  \Dingo\Api\Routing\Router  $router
     * @param  \Dingo\Api\Auth\Authenticator  $auth
     * @param  \Dingo\Api\Http\RateLimit\RateLimiter  $limiter
     * @return void
     */
    public function __construct(Router $router, Authenticator $auth, RateLimiter $limiter)
    {
        $this->router = $router;
        $this->auth = $auth;
        $this->limiter = $limiter;
    }

    /**
     * Perform rate limiting before a request is executed.
     * 
     * @param  \Dingo\Api\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $limit
     * @param  int  $expires
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function filter(Route $route, Request $request, $limit = 0, $expires = 0)
    {
        if ($this->requestIsInternal($request) || $this->requestIsRegular($request)) {
            return null;
        }

        $this->limiter->rateLimitRequest($request, $limit, $expires);

        if (! $this->limiter->requestWasRateLimited()) {
            return null;
        }

        $this->attachResponseAfterFilter();

        if ($this->limiter->exceededRateLimit()) {
            throw new AccessDeniedHttpException;
        }
    }

    /**
     * Attach the after filter to adjust the response.
     * 
     * @return void
     */
    protected function attachResponseAfterFilter()
    {
        $this->router->after(function (Request $request, Response $response) {
            $response->headers->set('X-RateLimit-Limit', $this->limiter->getThrottle()->getLimit());
            $response->headers->set('X-RateLimit-Remaining', $this->limiter->getRemainingLimit());
            $response->headers->set('X-RateLimit-Reset', $this->limiter->getRateLimitExpiration());
        });
    }
}

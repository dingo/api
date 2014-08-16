<?php

namespace Dingo\Api\Events;

use Dingo\Api\Auth\Shield;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\Routing\Router;
use Illuminate\Events\Dispatcher;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticationHandler
{
    /**
     * API router instance.
     * 
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * API authentication shield instance.
     * 
     * @var \Dingo\Api\Auth\Shield
     */
    protected $auth;

    /**
     * Create a new authentication handler instance.
     * 
     * @param  Dingo\Api\Routing\Router  $router
     * @param  Dingo\Api\Auth\Shield  $auth
     * @return void
     */
    public function __construct(Router $router, Dispatcher $events, Shield $auth)
    {
        $this->router = $router;
        $this->events = $events;
        $this->auth = $auth;
    }

    public function handleRequestAuthentication(Route $route, Request $request)
    {
        if ($request instanceof InternalRequest || $this->auth->user() || ! $this->router->requestTargettingApi($request)) {
            return null;
        }

        if ($route->isProtected()) {
            try {
                return $this->auth->authenticate($request, $route);
            } catch (UnauthorizedHttpException $exception) {
                return $this->events->until('router.exception', [$exception]);
            }
        }
    }
}

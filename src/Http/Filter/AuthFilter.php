<?php

namespace Dingo\Api\Http\Filter;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Authenticator;
use Illuminate\Events\Dispatcher;

class AuthFilter extends Filter
{
    /**
     * API router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * Illuminate events dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * API authenticator instance.
     *
     * @var \Dingo\Api\Auth\Authenticator
     */
    protected $auth;

    /**
     * Create a new authentication handler instance.
     *
     * @param Dingo\Api\Routing\Router     $router
     * @param Illuminate\Events\Dispatcher $events
     * @param Dingo\Api\Auth\Authenticator $auth
     *
     * @return void
     */
    public function __construct(Router $router, Dispatcher $events, Authenticator $auth)
    {
        $this->router = $router;
        $this->events = $events;
        $this->auth = $auth;
    }

    /**
     * Peform authentication before a request is executed.
     *
     * @param \Dingo\Api\Routing\Route $route
     * @param \Illuminate\Http\Request $request
     * @param dynamic                  $provider
     *
     * @return mixed
     */
    public function filter(Route $route, Request $request)
    {
        if ($this->routeNotProtected($route) || $this->userIsLogged()) {
            return;
        }

        $providers = array_merge(array_slice(func_get_args(), 2), $route->getAuthProviders());

        $this->auth->authenticate($providers);
    }
}

<?php

namespace Dingo\Api\Http\Filter;

use Dingo\Api\Auth\Auth;
use Dingo\Api\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Illuminate\Events\Dispatcher;

class Auth extends Filter
{
    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * Auth instance.
     *
     * @var \Dingo\Api\Auth\Auth
     */
    protected $auth;

    /**
     * Create a new authentication handler instance.
     *
     * @param Dingo\Api\Routing\Router $router
     * @param Dingo\Api\Auth\Auth      $auth
     *
     * @return void
     */
    public function __construct(Router $router, Auth $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    /**
     * Peform authentication before a route is executed.
     *
     * @param \Dingo\Api\Routing\Route $route
     * @param \Dingo\Api\Http\Request  $request
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

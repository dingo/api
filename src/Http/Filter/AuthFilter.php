<?php

namespace Dingo\Api\Http\Filter;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Authenticator;
use Illuminate\Events\Dispatcher;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
     * @param  Dingo\Api\Routing\Router  $router
     * @param  Dingo\Api\Auth\Shield  $auth
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
     * @param  \Dingo\Api\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function filter(Route $route, Request $request)
    {
        if ($this->requestIsInternal($request) || $this->requestIsRegular($request) || $this->routeNotProtected($route) || $this->userIsLogged()) {
            return null;
        }

        $providers = array_slice(func_get_args(), 2);

        try {
            return $this->auth->authenticate($providers);
        } catch (UnauthorizedHttpException $exception) {
            return $this->events->until('router.exception', [$exception]);
        }
    }
}

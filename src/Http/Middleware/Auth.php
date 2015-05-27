<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Auth as Authentication;

class Auth
{
    /**
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * @var \Dingo\Api\Auth\Auth
     */
    protected $auth;

    /**
     * @param \Dingo\Api\Routing\Router $router
     * @param \Dingo\Api\Auth\Auth $auth
     */
    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if ($route->isProtected() && ! $this->auth->check(false)) {
            $this->auth->authenticate($route->getAuthProviders());
        }

        return $next($request);
    }
}

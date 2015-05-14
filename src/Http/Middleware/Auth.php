<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\Auth as Authentication;

class Auth
{
    protected $router;

    protected $auth;

    public function __construct(Router $router, Authentication $auth)
    {
        $this->router = $router;
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        $route = $this->router->getCurrentRoute();

        if ($route->isProtected() && ! $this->auth->check(false)) {
            $this->auth->authenticate($route->getAuthProviders());
        }

        return $next($request);
    }
}

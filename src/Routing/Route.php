<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;


class Route
{
    /**
     * Route URI.
     *
     * @var string
     */
    protected $uri;

    /**
     * Array of HTTP methods.
     *
     * @var array
     */
    protected $methods;

    /**
     * Array of route action attributes.
     *
     * @var array
     */
    protected $action;

    /**
     * Array of versions this route will respond to.
     *
     * @var array
     */
    protected $versions;

    /**
     * Array of scopes for OAuth 2.0 authentication.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Indicates if the route is protected.
     *
     * @var bool
     */
    protected $protected = false;

    /**
     * Array of authentication providers.
     *
     * @var array
     */
    protected $authProviders = [];

    protected $rateLimit;

    protected $rateExpiration;

    /**
     * Create a new route instance.
     *
     * @param array|\Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request         $request
     *
     * @return void
     */
    public function __construct($route, Request $request)
    {
        $this->createRoute($route, $request);
    }

    /**
     * Create the route from the existing route and request instance.
     *
     * @param array|\Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request         $request
     *
     * @return void
     */
    protected function createRoute($route, Request $request)
    {
        if ($route instanceof IlluminateRoute) {
            $this->createFromLaravelRoute($route, $request);
        } else {
            $this->createFromLumenRoute($route, $request);
        }

        $this->versions = array_pull($this->action, 'version');
        $this->scopes = array_pull($this->action, 'scopes', []);
        $this->protected = array_pull($this->action, 'protected', false);
        $this->authProviders = array_pull($this->action, 'providers', []);
        $this->rateLimit = array_pull($this->action, 'limit', 0);
        $this->rateExpiration = array_pull($this->action, 'expires', 0);

        if (is_string($this->authProviders)) {
            $this->authProviders = explode('|', $this->authProviders);
        }
    }

    /**
     * Create a new route from a Laravel route.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Dingo\Api\Http\Request   $request
     *
     * @return void
     */
    protected function createFromLaravelRoute(IlluminateRoute $route, Request $request)
    {
        $this->uri = $route->getUri();
        $this->methods = $route->getMethods();
        $this->action = $route->getAction();
    }

    /**
     * Create a new route from a Lumen route.
     *
     * @param array                   $route
     * @param \Dingo\Api\Http\Request $request
     *
     * @return void
     */
    protected function createFromLumenRoute(array $route, Request $request)
    {
        $this->uri = ltrim($request->getRequestUri(), '/');
        $this->methods = (array) $request->getMethod();
        $this->action = $route[1];

        if ($request->getMethod() === 'GET') {
            $this->methods[] = 'HEAD';
        }
    }

    /**
     * Determine if the route is protected.
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->protected === true;
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function scopes()
    {
        return $this->getScopes();
    }

    /**
     * Get the route scopes.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Get the route authentication providers.
     *
     * @return array
     */
    public function getAuthProviders()
    {
        return $this->authProviders;
    }

    /**
     * Get the rate limit for this route.
     *
     * @return int
     */
    public function getRateLimit()
    {
        return $this->rateLimit;
    }

    /**
     * Get the rate limit expiration time for this route.
     *
     * @return int
     */
    public function getLimitExpiration()
    {
        return $this->rateExpiration;
    }
}

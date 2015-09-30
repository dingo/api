<?php

namespace Dingo\Api\Routing;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Dingo\Api\Contract\Routing\Adapter;

class Route
{
    /**
     * Routing adapter instance.
     *
     * @var \Dingo\Api\Routing\Adapter\Adapter
     */
    protected $adapter;

    /**
     * Container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

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
    protected $scopes;

    /**
     * Array of authentication providers.
     *
     * @var array
     */
    protected $authProviders;

    /**
     * The rate limit for this route.
     *
     * @var int
     */
    protected $rateLimit;

    /**
     * The expiration time for any rate limit set on this rate.
     *
     * @var int
     */
    protected $rateExpiration;

    /**
     * The throttle used by the route, takes precedence over rate limits.
     *
     * @return string|\Dingo\Api\Http\RateLimit\Throttle\Throttle
     */
    protected $throttle;

    /**
     * Controller instance.
     *
     * @var object
     */
    protected $controller;

    /**
     * Controller method name.
     *
     * @var string
     */
    protected $method;

    /**
     * Indicates if the request is conditional.
     *
     * @var bool
     */
    protected $conditionalRequest = true;

    /**
     * Middleware applied to route.
     *
     * @var array
     */
    protected $middleware;

    /**
     * Create a new route instance.
     *
     * @param \Dingo\Api\Routing\Adapter\Adapter $adapter
     * @param \Illuminate\Container\Container    $container
     * @param \Illuminate\Http\Request           $request
     * @param array|\Illuminate\Routing\Route    $route
     *
     * @return void
     */
    public function __construct(Adapter $adapter, Container $container, Request $request, $route)
    {
        $this->adapter = $adapter;
        $this->container = $container;

        list($this->uri, $this->methods, $this->action) = $this->adapter->getRouteProperties($route, $request);

        $this->versions = array_pull($this->action, 'version');
        $this->conditionalRequest = array_pull($this->action, 'conditionalRequest', true);
    }

    /**
     * Find the controller options and whether or not it will apply to this routes method.
     *
     * @param string   $option
     * @param \Closure $callback
     *
     * @return void
     */
    protected function findControllerOptions($option, Closure $callback)
    {
        if ($this->usesController()) {
            $properties = $this->getControllerProperties();

            foreach ($properties[$option] as $value) {
                if (! $this->optionsApplyToControllerMethod($value['options'])) {
                    continue;
                }

                $callback($value);
            }
        }
    }

    /**
     * Get the controller method properties.
     *
     * @return array
     */
    protected function getControllerProperties()
    {
        $method = $this->getControllerPropertiesMethodName();

        return array_merge(
            ['scope' => [], 'providers' => [], 'rateLimit' => [], 'throttles' => []],
            $this->controller->$method()
        );
    }

    /**
     * Get the name of method to get the controller properties.
     *
     * @return string
     */
    protected function getControllerPropertiesMethodName()
    {
        return 'getMethodProperties';
    }

    /**
     * Determine if a controller method is in an array of options.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function optionsApplyToControllerMethod(array $options)
    {
        if (empty($options)) {
            return true;
        } elseif (isset($options['only']) && in_array($this->method, $this->explodeOnPipes($options['only']))) {
            return true;
        } elseif (isset($options['except']) && in_array($this->method, $this->explodeOnPipes($options['except']))) {
            return false;
        } elseif (in_array($this->method, $this->explodeOnPipes($options))) {
            return true;
        }

        return false;
    }

    /**
     * Explode a value on a pipe delimiter.
     *
     * @param string|array $value
     *
     * @return array
     */
    protected function explodeOnPipes($value)
    {
        return is_string($value) ? explode('|', $value) : $value;
    }

    /**
     * Determine if the route uses a controller with the helpers trait.
     *
     * @return bool
     */
    public function usesController()
    {
        $controller = $this->getController();

        return ! is_null($controller) && method_exists($controller, $this->getControllerPropertiesMethodName());
    }

    /**
     * Get the routes controller instance.
     *
     * @return mixed
     */
    public function getController()
    {
        if (! isset($this->action['uses']) || ! is_string($this->action['uses'])) {
            return;
        } elseif (isset($this->controller)) {
            return $this->controller;
        }

        if (str_contains($this->action['uses'], '@')) {
            list($controller, $this->method) = explode('@', $this->action['uses']);

            return $this->controller = $this->container->make($controller);
        }
    }

    /**
     * Get the middleware applied to the route.
     *
     * @return array
     */
    public function getMiddleware()
    {
        if (! is_null($this->middleware)) {
            return $this->middleware;
        }

        $this->middleware = [];

        foreach ($this->action['middleware'] as $middleware) {
            list($middleware, $options) = array_merge(explode(':', $middleware), [[]]);

            $this->middleware[$middleware] = $options;
        }

        if ($controller = $this->getController()) {
            $this->middleware = array_merge($this->middleware, $controller->getMiddleware());
        }

        return $this->middleware;
    }

    /**
     * Determine if the route has a throttle.
     *
     * @return bool
     */
    public function hasThrottle()
    {
        return ! is_null($this->getThrottle());
    }

    /**
     * Get the route throttle.
     *
     * @return string|\Dingo\Api\Http\RateLimit\Throttle\Throttle
     */
    public function getThrottle()
    {
        if (is_null($this->throttle)) {
            $this->throttle = array_pull($this->action, 'throttle', []);

            $this->findControllerOptions('throttles', function ($value) {
                $this->throttle = $value['throttle'];
            });

            if (is_string($this->throttle)) {
                $this->throttle = $this->container->make($this->throttle);
            }
        }

        return $this->throttle;
    }

    /**
     * Get the name of the route.
     *
     * @return string
     */
    public function getName()
    {
        return array_get($this->action, 'as', null);
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
        if (is_null($this->scopes)) {
            $this->scopes = array_pull($this->action, 'scopes', []);

            $this->findControllerOptions('scopes', function ($value) {
                $this->scopes = array_merge($this->scopes, $value['scopes']);
            });
        }

        return $this->scopes;
    }

    /**
     * Get the route authentication providers.
     *
     * @return array
     */
    public function getAuthProviders()
    {
        if (is_null($this->authProviders)) {
            $this->authProviders = array_pull($this->action, 'providers', []);

            $this->findControllerOptions('providers', function ($value) {
                $this->authProviders = array_merge($this->authProviders, $value['providers']);
            });
        }

        return $this->authProviders;
    }

    /**
     * Get the rate limit for this route.
     *
     * @return int
     */
    public function getRateLimit()
    {
        if (is_null($this->rateLimit)) {
            $this->rateLimit = array_pull($this->action, 'limit', 0);

            $this->findControllerOptions('rateLimit', function ($value) {
                $this->rateLimit = $value['limit'];
            });
        }

        return $this->rateLimit;
    }

    /**
     * Get the rate limit expiration time for this route.
     *
     * @return int
     */
    public function getRateExpiration()
    {
        if (is_null($this->rateExpiration)) {
            $this->rateExpiration = array_pull($this->action, 'expires', 0);

            $this->findControllerOptions('rateLimit', function ($value) {
                $this->rateExpiration = $value['expires'];
            });
        }

        return $this->rateExpiration;
    }

    /**
     * Determine if the request is conditional.
     *
     * @return bool
     */
    public function requestIsConditional()
    {
        return $this->conditionalRequest === true;
    }

    /**
     * Get the route action.
     *
     * @return array
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the action name for the route.
     *
     * @return string
     */
    public function getActionName()
    {
        return is_string($this->action['uses']) ? $this->action['uses'] : 'Closure';
    }

    /**
     * Get the versions for the route.
     *
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * Get the versions for the route.
     *
     * @return array
     */
    public function versions()
    {
        return $this->getVersions();
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->uri();
    }

    /**
     * Get the URI associated with the route.
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods();
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * Determine if the route only responds to HTTP requests.
     *
     * @return bool
     */
    public function httpOnly()
    {
        return in_array('http', $this->action, true);
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function httpsOnly()
    {
        return $this->secure();
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * @return bool
     */
    public function secure()
    {
        return in_array('https', $this->action, true);
    }

    /**
     * Get the domain defined for the route.
     *
     * @return string|null
     */
    public function domain()
    {
        return array_get($this->action, 'domain');
    }
}

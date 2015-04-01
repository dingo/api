<?php

namespace Dingo\Api\Routing;

use Exception;
use Dingo\Api\Properties;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Dingo\Api\Http\ResponseBuilder;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Container\Container;
use Dingo\Api\Http\Response as ApiResponse;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Http\Response as IlluminateResponse;
use Dingo\Api\Exception\InvalidAcceptHeaderException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router extends IlluminateRouter
{
    /**
     * API properties instance.
     *
     * @var \Dingo\Api\Properties
     */
    protected $properties;

    /**
     * API version collection instance.
     *
     * @var array
     */
    protected $api;

    /**
     * Current API version.
     *
     * @var string
     */
    protected $currentVersion;

    /**
     * Current API format.
     *
     * @var string
     */
    protected $currentFormat;

    /**
     * Array of requests targetting the API.
     *
     * @var array
     */
    protected $apiRequests = [];

    /**
     * Indicates if the request is a conditional request.
     *
     * @var bool
     */
    protected $conditionalRequest;

    /**
     * Indicates if the request is in strict mode.
     *
     * @var bool
     */
    protected $strict;

    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->events = $events;
        $this->routes = new RouteCollection;
        $this->container = $container ?: new Container;
    }

    /**
     * Register an API group.
     *
     * @param array|string $options
     * @param callable     $callback
     *
     * @throws \BadMethodCallException
     *
     * @return void
     */
    public function api($options, callable $callback)
    {
        $options = $this->setupGroupOptions($options);

        $this->createRouteCollections($options);

        $this->group($options, $callback);
    }

    /**
     * Create the route collections with the given options.
     *
     * @param array $options
     *
     * @return void
     */
    protected function createRouteCollections(array $options)
    {
        foreach ($options['version'] as $version) {
            $this->api->add($version, $options);
        }
    }

    /**
     * Setup the API group options.
     *
     * @param array|string $options
     *
     * @return array
     */
    protected function setupGroupOptions($options)
    {
        if (is_string($options)) {
            $options = ['version' => $options];
        } elseif (! isset($options['version'])) {
            throw new BadMethodCallException('Unable to register API route group without a version.');
        }

        $options['api'] = true;

        $options['version'] = (array) $options['version'];

        if (! isset($options['prefix']) && $prefix = $this->properties->getPrefix()) {
            $options['prefix'] = $prefix;
        }

        if (! isset($options['domain']) && $domain = $this->properties->getDomain()) {
            $options['domain'] = $domain;
        }

        if (isset($options['conditional_request'])) {
            $this->conditionalRequest = $options['conditional_request'];
        }

        return $options;
    }

    /**
     * Determine if the router is currently routing to the API.
     *
     * @return bool
     */
    protected function routingToApi()
    {
        return ! empty($this->groupStack) && array_key_exists('version', last($this->groupStack)) === true;
    }

    /**
     * Add an existing collection of routes.
     *
     * @param \Illuminate\Routing\RouteCollection $routes
     *
     * @return void
     */
    public function addExistingRoutes(IlluminateRouteCollection $routes)
    {
        foreach ($routes as $route) {
            $action = array_except($route->getAction(), 'uses');

            $uri = $route->getUri();

            if ($prefix = $route->getPrefix()) {
                $uri = substr_replace($uri, '', 0, strlen($prefix)).'/';
            }

            $action['uses'] = array_pull($action, 'controller');

            $this->addRoute($route->getMethods(), $uri, $action);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newRoute($methods, $uri, $action)
    {
        if ($this->routingToApi()) {
            return (new Route($methods, $uri, $action))->setContainer($this->container);
        }

        return parent::newRoute($methods, $uri, $action);
    }

    /**
     * Add a new route to an API collection.
     *
     * @param \Illuminate\Routing\Route $route
     *
     * @return \Illuminate\Routing\Route
     */
    protected function addApiRoute($route)
    {
        $options = last($this->groupStack);

        foreach ($this->api->getByOptions($options) as $collection) {
            $collection->add($route);
        }

        return $route;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareResponse($request, $response)
    {
        if ($response instanceof ResponseBuilder) {
            $response = $response->build();
        }

        $response = parent::prepareResponse($request, $response);

        if ($this->isApiRequest($request)) {
            if ($response instanceof IlluminateResponse) {
                $response = ApiResponse::makeFromExisting($response);
            }

            if ($response->isSuccessful() && $this->requestsAreConditional()) {
                if (! $response->headers->has('ETag')) {
                    $response->setEtag(md5($response->getContent()));
                }

                $response->isNotModified($request);
            }
        }

        return $response;
    }

    /**
     * Parse a requests accept header.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    protected function parseAcceptHeader(Request $request)
    {
        if (preg_match('#application/vnd\.'.$this->properties->getVendor().'.(v[\d\.]+)\+(\w+)#', $request->header('accept'), $matches)) {
            return array_slice($matches, 1);
        } elseif ($this->isStrict()) {
            throw new InvalidAcceptHeaderException('Unable to match the "Accept" header for the API request.');
        }

        return [$this->properties->getVersion(), $this->properties->getFormat()];
    }

    /**
     * Get the current API format.
     *
     * @return string
     */
    public function getCurrentFormat()
    {
        return $this->currentFormat;
    }

    /**
     * Get the current API version.
     *
     * @return string
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * Get a controller inspector instance.
     *
     * @return \Dingo\Api\Routing\ControllerInspector
     */
    public function getInspector()
    {
        return $this->inspector ?: $this->inspector = new ControllerInspector;
    }

    /**
     * Set the current request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function setCurrentRequest(Request $request)
    {
        $this->currentRequest = $request;
    }

    /**
     * Set the current route.
     *
     * @param \Illuminate\Routing\Route $route
     *
     * @return void
     */
    public function setCurrentRoute(IlluminateRoute $route)
    {
        $this->current = $route;
    }

    /**
     * Get the API groups collection containing the API routes.
     *
     * @return \Dingo\Api\Routing\GroupCollection
     */
    public function getApiGroups()
    {
        return $this->api;
    }

    /**
     * Determine if conditional requests are enabled.
     *
     * @return string
     */
    public function requestsAreConditional()
    {
        return $this->conditionalRequest;
    }

    /**
     * Enable or disable conditional requests.
     *
     * @param bool $conditionalRequest
     *
     * @return void
     */
    public function setConditionalRequest($conditionalRequest)
    {
        $this->conditionalRequest = $conditionalRequest;
    }

    /**
     * Determine if the request should be treated as strict.
     *
     * @return bool
     */
    public function isStrict()
    {
        return $this->strict;
    }

    /**
     * Enable or disable strict mode.
     *
     * @param bool $strict
     *
     * @return void
     */
    public function setStrict($strict)
    {
        $this->strict = $strict;
    }
}

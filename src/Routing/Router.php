<?php

namespace Dingo\Api\Routing;

use Exception;
use Dingo\Api\Config;
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
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router extends IlluminateRouter
{
    /**
     * API config instance.
     *
     * @var \Dingo\Api\Config
     */
    protected $config;

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

    /**
     * Name of the authentication filter.
     *
     * @var string
     */
    const API_FILTER_AUTH = 'api.auth';

    /**
     * Name of the throttling filter.
     *
     * @var string
     */
    const API_FILTER_THROTTLE = 'api.throttle';

    /**
     * Create a new router instance.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @param  \Dingo\Api\Routing\Config  $config
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Dispatcher $events, Config $config, Container $container = null)
    {
        $this->config = $config;
        $this->api = new GroupCollection($config);

        parent::__construct($events, $container);
    }

    /**
     * Register an API group.
     *
     * @param  array|string  $options
     * @param  callable  $callback
     * @return void
     * @throws \BadMethodCallException
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
     * @param  array  $options
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
     * @param  array|string  $options
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

        if (! isset($options['prefix'])) {
            $options['prefix'] = $this->config->getPrefix();
        }

        if (! isset($options['domain'])) {
            $options['domain'] = $this->config->getDomain();
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
        return ! empty($this->groupStack) && array_get(last($this->groupStack), 'api', false) === true;
    }

    /**
     * Add an existing collection of routes.
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
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
     * Dispatch the request to the application and return either a regular response
     * or an API response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $morph
     * @return \Illuminate\Http\Response|\Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function dispatch(Request $request, $morph = true)
    {
        if (! $this->isApiRequest($request)) {
            return parent::dispatch($request);
        }

        list ($version, $format) = $this->parseAcceptHeader($request);

        $this->currentVersion = $version;
        $this->currentFormat = $format;

        $this->container->instance('Illuminate\Http\Request', $request);

        try {
            $response = parent::dispatch($request);

            // We'll try to set the request and the response on the formatter
            // now so that we can catch any exceptions that may be thrown
            // due to a badly requested format.
            $response->getFormatter($format)
                     ->setRequest($request)
                     ->setResponse($response);
        } catch (Exception $exception) {
            if ($request instanceof InternalRequest) {
                throw $exception;
            } else {
                $response = $this->prepareResponse(
                    $request,
                    $this->events->until('router.exception', [$exception])
                );

                // When an exception is thrown it halts execution of the dispatch. We'll
                // call the attached after filters for caught exceptions still.
                $this->callFilter('after', $request, $response);
            }
        }

        // This goes hand in hand with the above. We'll check to see if a
        // formatter exists for the requested response format. If not
        // then we'll revert to the default format because we are
        // most likely formatting an error response.
        if ($morph) {
            if (! $response->hasFormatter($format)) {
                $format = $this->defaultFormat;
            }

            $response = $response->morph($format);
        }

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    protected function newRoute($methods, $uri, $action)
    {
        if ($this->routingToApi()) {
            return new Route($methods, $uri, $action);
        }

        return parent::newRoute($methods, $uri, $action);
    }

    /**
     * Add a new route to either the routers collection or an API collection.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  callable|array|string  $action
     * @return \Illuminate\Routing\Route
     */
    protected function addRoute($methods, $uri, $action)
    {
        $route = $this->createRoute($methods, $uri, $action);

        if ($this->routingToApi()) {
            return $this->addApiRoute($this->attachApiFilters($route));
        }

        return $this->routes->add($route);
    }

    /**
     * Add a new route to an API collection.
     *
     * @param  \Illuminate\Routing\Route  $route
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
     * Attach the API before filters to the route.
     *
     * @param  \Dingo\Api\Routing\Route  $route
     * @return \Dingo\Api\Routing\Route
     */
    protected function attachApiFilters(Route $route)
    {
        $filters = $route->beforeFilters();

        foreach ([static::API_FILTER_AUTH, static::API_FILTER_THROTTLE] as $filter) {
            if (! isset($filters[$filter])) {
                $route->before($filter);
            }
        }

        return $route;
    }

    /**
     * {@inheritDoc}
     */
    protected function findRoute($request)
    {
        if ($this->isApiRequest($request)) {
            $route = $this->api->getByVersion($this->currentVersion)->match($request);
        } else {
            $route = $this->routes->match($request);
        }

        return $this->current = $this->substituteBindings($route);
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
     * Determine if the request is an API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function isApiRequest($request)
    {
        if ($this->api->isEmpty()) {
            return false;
        } elseif (isset($this->apiRequests[$key = sha1($request)])) {
            return $this->apiRequests[$key];
        }

        $collection = $this->api->getByRequest($request) ?: $this->api->getDefault();

        try {
            $collection->match($request);
        } catch (NotFoundHttpException $exception) {
            return $this->apiRequests[$key] = false;
        } catch (MethodNotAllowedHttpException $exception) {
            // If a method is not allowed then we can say that a route was matched
            // so the request is still targetting the API. This allows developers
            // to provide better error responses when clients send bad requests.
        }

        return $this->apiRequests[$key] = true;
    }

    /**
     * Parse a requests accept header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function parseAcceptHeader(Request $request)
    {
        if (preg_match('#application/vnd\.'.$this->config->getVendor().'.(v[\d\.]+)\+(\w+)#', $request->header('accept'), $matches)) {
            return array_slice($matches, 1);
        } elseif ($this->isStrict()) {
            throw new InvalidAcceptHeaderException('Unable to match the "Accept" header for the API request.');
        }

        return [$this->config->getVersion(), $this->config->getFormat()];
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
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setCurrentRequest(Request $request)
    {
        $this->currentRequest = $request;
    }

    /**
     * Set the current route.
     *
     * @param  \Illuminate\Routing\Route  $route
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
     * @param  bool  $conditionalRequest
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
     * @param  bool  $strict
     * @return void
     */
    public function setStrict($strict)
    {
        $this->strict = $strict;
    }
}

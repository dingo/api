<?php

namespace Dingo\Api\Routing;

use Exception;
use BadMethodCallException;
use Illuminate\Http\Request;
use Dingo\Api\Http\ResponseBuilder;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Response as ApiResponse;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router extends IlluminateRouter
{
    /**
     * An array of API route collections.
     *
     * @var array
     */
    protected $api = [];

    /**
     * The default API version.
     *
     * @var string
     */
    protected $defaultVersion = 'v1';

    /**
     * The default API prefix.
     *
     * @var string
     */
    protected $defaultPrefix;

    /**
     * The default API domain.
     *
     * @var string
     */
    protected $defaultDomain;

    /**
     * The default API format.
     *
     * @var string
     */
    protected $defaultFormat = 'json';

    /**
     * The API vendor.
     *
     * @var string
     */
    protected $vendor;

    /**
     * Requested API version.
     *
     * @var string
     */
    protected $requestedVersion;

    /**
     * Requested format.
     *
     * @var string
     */
    protected $requestedFormat;

    /**
     * Indicates if conditional requests are enabled or disabled.
     *
     * @var bool
     */
    protected $conditionalRequest = true;

    /**
     * Array of requests targetting the API.
     *
     * @var array
     */
    protected $requestsTargettingApi = [];

    /**
     * Indicates if API routes are being added.
     *
     * @var bool
     */
    protected $addingApiRoutes = false;

    /**
     * Register an API group.
     *
     * @param  array  $options
     * @param  callable  $callback
     * @return void
     * @throws \BadMethodCallException
     */
    public function api($options, callable $callback)
    {
        if (! isset($options['version'])) {
            throw new BadMethodCallException('Unable to register API without an API version.');
        }

        $options['version'] = (array) $options['version'];

        $options[] = 'api';

        if (! isset($options['prefix'])) {
            $options['prefix'] = $this->defaultPrefix;
        }

        if (! isset($options['domain'])) {
            $options['domain'] = $this->defaultDomain;
        }

        if (isset($options['conditional_request'])) {
            $this->conditionalRequest = $options['conditional_request'];
        }

        foreach ($options['version'] as $version) {
            if (! isset($this->api[$version])) {
                $this->api[$version] = new RouteCollection($version, array_except($options, 'version'));
            }
        }

        $this->addingApiRoutes = true;

        $this->group($options, $callback);

        $this->addingApiRoutes = false;
    }

    /**
     * Dispatch the request to the application and return either a regular response
     * or an API response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function dispatch(Request $request)
    {
        if (! $this->requestTargettingApi($request)) {
            return parent::dispatch($request);
        }

        list ($version, $format) = $this->parseAcceptHeader($request);

        $this->requestedVersion = $version;
        $this->requestedFormat = $format;

        $this->container->instance('Illuminate\Http\Request', $request);

        try {
            $response = parent::dispatch($request);
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

        $this->container->forgetInstance('Illuminate\Http\Request');

        if ($request instanceof InternalRequest) {
            return $response;
        }

        $response->getFormatter($format)->setRequest($request)->setResponse($response);

        return $response->morph($this->requestedFormat);
    }

    /**
     * {@inheritDoc}
     */
    protected function newRoute($methods, $uri, $action)
    {
        if ($this->addingApiRoutes) {
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

        if ($this->addingApiRoutes) {
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
        $versions = array_get(last($this->groupStack), 'version', []);

        foreach ($versions as $version) {
            if ($collection = $this->getApiRouteCollection($version)) {
                $collection->add($route);
            }
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

        foreach (['api.auth', 'api.throttle'] as $filter) {
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
        if ($this->requestTargettingApi($request)) {
            $route = $this->getApiRouteCollection($this->requestedVersion)->match($request);
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

        if ($this->requestTargettingApi($request)) {
            if ($response instanceof IlluminateResponse) {
                $response = ApiResponse::makeFromExisting($response);
            }

            if ($response->isSuccessful() && $this->getConditionalRequest()) {
                if (! $response->headers->has('ETag')) {
                    $response->setEtag(md5($response->getContent()));
                }

                $response->isNotModified($request);
            }
        }

        return $response;
    }

    /**
     * Determine if the current request is targetting an API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function requestTargettingApi($request)
    {
        if (empty($this->api)) {
            return false;
        } elseif (isset($this->requestsTargettingApi[$key = sha1($request)])) {
            return $this->requestsTargettingApi[$key];
        }

        $collection = $this->getApiRouteCollectionFromRequest($request) ?: $this->getDefaultApiRouteCollection();

        try {
            $collection->match($request);
        } catch (NotFoundHttpException $exception) {
            return $this->requestsTargettingApi[$key] = false;
        } catch (MethodNotAllowedHttpException $exception) {
            // If a method is not allowed then we can say that a route was matched
            // so the request is still targetting the API. This allows developers
            // to provide better error responses when clients send bad requests.
        }

        return $this->requestsTargettingApi[$key] = true;
    }

    /**
     * Parse a requests accept header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function parseAcceptHeader(Request $request)
    {
        if (preg_match('#application/vnd\.'.$this->vendor.'.(v[\d\.]+)\+(\w+)#', $request->header('accept'), $matches)) {
            return array_slice($matches, 1);
        }

        return [$this->defaultVersion, $this->defaultFormat];
    }

    /**
     * Get a matching API route collection from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return null|\Dingo\Api\Routing\ApiRouteCollection
     */
    public function getApiRouteCollectionFromRequest(Request $request)
    {
        return array_first($this->api, function ($key, $collection) use ($request) {
            return $collection->matchesRequest($request);
        });
    }

    /**
     * Get the default API route collection.
     *
     * @return \Dingo\Api\Routing\ApiRouteCollection|null
     */
    public function getDefaultApiRouteCollection()
    {
        return $this->getApiRouteCollection($this->defaultVersion);
    }

    /**
     * Get an API route collection for a given version.
     *
     * @param  string  $version
     * @return \Dingo\Api\Routing\ApiRouteCollection|null
     */
    public function getApiRouteCollection($version)
    {
        return isset($this->api[$version]) ? $this->api[$version] : null;
    }

    /**
     * Set the default API version.
     *
     * @param  string  $defaultVersion
     * @return void
     */
    public function setDefaultVersion($defaultVersion)
    {
        $this->defaultVersion = $defaultVersion;
    }

    /**
     * Get the default API version.
     *
     * @return string
     */
    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    /**
     * Set the default API prefix.
     *
     * @param  string  $defaultPrefix
     * @return void
     */
    public function setDefaultPrefix($defaultPrefix)
    {
        $this->defaultPrefix = $defaultPrefix;
    }

    /**
     * Get the default API prefix.
     *
     * @return string
     */
    public function getDefaultPrefix()
    {
        return $this->defaultPrefix;
    }

    /**
     * Set the default API domain.
     *
     * @param  string  $defaultDomain
     * @return void
     */
    public function setDefaultDomain($defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * Get the default API domain.
     *
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->defaultDomain;
    }

    /**
     * Set the API vendor.
     *
     * @param  string  $vendor
     * @return void
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * Get the API vendor.
     *
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Set the default API format.
     *
     * @param  string  $defaultFormat
     * @return void
     */
    public function setDefaultFormat($defaultFormat)
    {
        $this->defaultFormat = $defaultFormat;
    }

    /**
     * Get the default API format.
     *
     * @return string
     */
    public function getDefaultFormat()
    {
        return $this->defaultFormat;
    }

    /**
     * Get the requested version.
     *
     * @return string
     */
    public function getRequestedVersion()
    {
        return $this->requestedVersion;
    }

    /**
     * Get the requested format.
     *
     * @return string
     */
    public function getRequestedFormat()
    {
        return $this->requestedFormat;
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
     * Get the array of registered API route collections.
     *
     * @return array
     */
    public function getApiRoutes()
    {
        return $this->api;
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
     * Check conditional requests are enabled.
     *
     * @return bool
     */
    public function getConditionalRequest()
    {
        return $this->conditionalRequest;
    }
}

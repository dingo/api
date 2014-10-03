<?php

namespace Dingo\Api\Routing;

use Exception;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Dingo\Api\ExceptionHandler;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Response as ApiResponse;
use Illuminate\Routing\Router as IlluminateRouter;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router extends IlluminateRouter
{
    /**
     * The API route collections.
     *
     * @param array
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
     * Exception handler instance.
     *
     * @var \Dingo\Api\ExceptionHandler
     */
    protected $exceptionHandler;

    /**
     * Controller reviser instance.
     *
     * @var \Dingo\Api\Routing\ControllerReviser
     */
    protected $reviser;

    /**
     * Array of requests targetting the API.
     *
     * @var array
     */
    protected $requestsTargettingApi = [];

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
                $this->api[$version] = new ApiRouteCollection($version, array_except($options, 'version'));
            }
        }

        $this->group($options, $callback);
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

        $this->container->instance('Illuminate\Http\Request', $request);

        ApiResponse::getTransformer()->setRequest($request);

        try {
            $response = parent::dispatch($request);
        } catch (Exception $exception) {
            if ($request instanceof InternalRequest) {
                throw $exception;
            } else {
                $response = $this->handleException($exception);
            }
        }

        $this->container->forgetInstance('Illuminate\Http\Request');

        return $response instanceof ApiResponse ? $response->morph($this->requestedFormat) : $response;
    }

    /**
     * Handle exception thrown when dispatching a request.
     *
     * @param  \Exception  $exception
     * @return \Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function handleException(Exception $exception)
    {
        // If the exception handler will handle the given exception then we'll fire
        // the callback registered to the handler and return the response.
        if ($this->exceptionHandler->willHandle($exception)) {
            $response = $this->exceptionHandler->handle($exception);

            return ApiResponse::makeFromExisting($response);
        } elseif (! $exception instanceof HttpExceptionInterface) {
            throw $exception;
        }

        if (! $message = $exception->getMessage()) {
            $message = sprintf('%d %s', $exception->getStatusCode(), ApiResponse::$statusTexts[$exception->getStatusCode()]);
        }

        $response = ['message' => $message];

        if ($exception instanceof ResourceException and $exception->hasErrors()) {
            $response['errors'] = $exception->getErrors();
        }

        if ($code = $exception->getCode()) {
            $response['code'] = $code;
        }

        return new ApiResponse($response, $exception->getStatusCode(), $exception->getHeaders());
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

        if ($this->routeTargettingApi($route)) {
            return $this->addApiRoute($route);
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
        // Since the groups action gets merged with the routes we need to make
        // sure that if the route supplied its own protection that we grab
        // that protection status from the array after the merge.
        $action = $route->getAction();

        if (count($this->groupStack) > 0 and isset($action['protected'])) {
            $action['protected'] = is_array($action['protected']) ? last($action['protected']) : $action['protected'];

            $route->setAction($action);
        }

        $versions = array_get(last($this->groupStack), 'version', []);

        foreach ($versions as $version) {
            if ($collection = $this->getApiRouteCollection($version)) {
                $collection->add($route);
            }
        }

        return $route;
    }

    /**
     * Find a route either from the routers collection or the API collection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        if ($this->requestTargettingApi($request)) {
            list ($this->requestedVersion, $this->requestedFormat) = $this->parseAcceptHeader($request);

            $this->current = $route = $this->getApiRouteCollection($this->requestedVersion)->match($request);

            return $this->substituteBindings($route);
        }

        return parent::findRoute($request);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareResponse($request, $response)
    {
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
        }

        if (isset($this->requestsTargettingApi[$key = sha1($request)])) {
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
            // to provide better error responses when a client sends a bad
            // request.
        }

        return $this->requestsTargettingApi[$key] = true;
    }

    /**
     * Parse a requests accept header.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function parseAcceptHeader($request)
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
        return array_get($this->api, $version);
    }

    /**
     * Determine if a route is targetting the API.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    public function routeTargettingApi($route)
    {
        $key = array_search('api', $route->getAction(), true);

        return is_numeric($key);
    }

    /**
     * Set the exception handler instance.
     *
     * @param  \Dingo\Api\ExceptionHandler
     * @return void
     */
    public function setExceptionHandler(ExceptionHandler $exceptionHandler)
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Get the exception handler instance.
     *
     * @return \Dingo\Api\ExceptionHandler
     */
    public function getExceptionHandler()
    {
        return $this->exceptionHandler;
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
     * @param  string  $defaultformat
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
     * Set the controller reviser instance.
     *
     * @param  \Dingo\Api\Routing\ControllerReviser  $reviser
     * @return void
     */
    public function setControllerReviser(ControllerReviser $reviser)
    {
        $this->reviser = $reviser;
    }

    /**
     * Get the controller reviser instance.
     *
     * @return \Dingo\Api\Routing\ControllerReviser
     */
    public function getControllerReviser()
    {
        return $this->reviser ?: new ControllerReviser($this->container);
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
    public function setCurrentRoute(Route $route)
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

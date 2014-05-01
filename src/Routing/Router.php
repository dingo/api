<?php namespace Dingo\Api\Routing;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\ExceptionHandler;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Routing\Router as IlluminateRouter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Router extends IlluminateRouter {

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
	 * Exception handler instance.
	 * 
	 * @var \Dingo\Api\ExceptionHandler
	 */
	protected $exceptionHandler;

	/**
	 * Array of parsed request "Accept" headers.
	 * 
	 * @var array
	 */
	protected $parsedAcceptHeaders = [];

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
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function api($options, Closure $callback)
	{
		if ( ! isset($options['version']))
		{
			throw new BadMethodCallException('Unable to register API without an API version.');
		}

		$options['version'] = (array) $options['version'];

		$options[] = 'api';

		if ( ! isset($options['prefix']))
		{
			$options['prefix'] = $this->defaultPrefix;
		}

		if ( ! isset($options['domain']))
		{
			$options['domain'] = $this->defaultDomain;
		}

		// If a collection for this version does not already exist we'll
		// create a new collection for this version.
		foreach ($options['version'] as $version)
		{
			if ( ! isset($this->api[$version]))
			{
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
	 */
	public function dispatch(Request $request)
	{
		$this->container->instance('Illuminate\Http\Request', $request);

		Response::getTransformer()->setRequest($request);

		try
		{
			$response = parent::dispatch($request);
		}
		catch (HttpExceptionInterface $exception)
		{
			// If an exception is caught and we are currently routing an API request then
			// we'll handle this exception by building a new response from it. This
			// allows the API to gracefully handle its own exceptions.
			if ($this->requestTargettingApi($request) and ! $request instanceof InternalRequest)
			{
				$response = $this->handleException($exception);
			}

			// If the request was an internal request then we will rethrow the exception
			// so that developers can easily catch them and adjust ther esponse
			// themselves.
			else
			{
				throw $exception;
			}
		}

		$this->container->forgetInstance('Illuminate\Http\Request');

		if ($this->requestTargettingApi($request))
		{
			$response = Response::makeFromExisting($response)->morph($this->requestedFormat);
		}

		return $response;
	}

	/**
	 * Handle exception thrown when dispatching a request.
	 * 
	 * @param  \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface  $exception
	 * @return \Dingo\Api\Http\Response
	 */
	public function handleException(HttpExceptionInterface $exception)
	{
		// If the exception handler will handle the given exception then we'll fire
		// the callback registered to the handler and return the response.
		if ($this->exceptionHandler->willHandle($exception))
		{
			$response = $this->exceptionHandler->handle($exception);

			return Response::makeFromExisting($response);
		}

		if ( ! $message = $exception->getMessage())
		{
			$message = sprintf('%d %s', $exception->getStatusCode(), Response::$statusTexts[$exception->getStatusCode()]);
		}

		if ($exception instanceof ResourceException)
		{
			$message = ['message' => $message];

			if ($exception->hasErrors()) $message['errors'] = $exception->errors();
		}

		return new Response($message, $exception->getStatusCode());
	}

	/**
	 * Add a new route to either the routers collection or an API collection.
	 * 
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	protected function addRoute($methods, $uri, $action)
	{
		$route = $this->createRoute($methods, $uri, $action);

		if ($this->routeTargettingApi($route))
		{
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

		if (count($this->groupStack) > 0 and isset($action['protected']))
		{
			$action['protected'] = is_array($action['protected']) ? last($action['protected']) : $action['protected'];

			$route->setAction($action);
		}

		$versions = array_get(last($this->groupStack), 'version', []);

		foreach ($versions as $version)
		{
			$this->getApiRouteCollection($version)->add($route);
		}

		return $route;
	}

	/**
	 * Create a new route instance.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  mixed   $action
	 * @return \Illuminate\Routing\Route
	 */
	protected function createRoute($methods, $uri, $action)
	{
		$route = parent::createRoute($methods, $uri, $action);

		if ($this->routeTargettingApi($route) and $this->routingToController($action))
		{
			$route = $this->adjustRouteForApiController($route);
		}

		return $route;
	}

	/**
	 * Adjust the routes action for an API controller.
	 * 
	 * @param  \Illuminate\Routing\Route  $route
	 * @return \Illuminate\Routing\Route
	 */
	protected function adjustRouteForApiController($route)
	{
		list ($class, $method) = explode('@', $route->getActionName());

		$controller = $this->container->make($class);

		if ($controller instanceof \Dingo\Api\Routing\Controller)
		{
			$route = $this->controllerMethodProtected($route, $controller, $method);

			$route = $this->controllerMethodScopes($route, $controller, $method);
		}

		return $route;
	}

	/**
	 * Adjust the scopes of a controller method. Scopes defined
	 * on the controller are merged with those defined
	 * in the route definition.
	 * 
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  \Dingo\Api\Routing\Controller  $controller
	 * @param  string  $method
	 * @return \Illuminate\Routing\Route
	 */
	protected function controllerMethodScopes($route, $controller, $method)
	{
		$action = $route->getAction();

		if ( ! isset($action['scopes']))
		{
			$action['scopes'] = [];
		}

		$action['scopes'] = (array) $action['scopes'];

		$scopedMethods = $controller->getScopedMethods();

		// A wildcard can be used to attach scopes to all controller methods so
		// we'll merge any scopes here
		if (isset($scopedMethods['*']))
		{
			$action['scopes'] = array_merge($action['scopes'], $scopedMethods['*']);
		}

		if (isset($scopedMethods[$method]))
		{
			$action['scopes'] = array_merge($action['scopes'], $scopedMethods[$method]);
		}

		$route->setAction($action);

		return $route;
	}

	/**
	 * Adjust the protected state of a controller method.
	 * 
	 * @param  \Illuminate\Routing\Route  $route
	 * @param  \Dingo\Api\Routing\Controller  $controller
	 * @param  string  $method
	 * @return \Illuminate\Routing\Route
	 */
	protected function controllerMethodProtected($route, $controller, $method)
	{
		$action = $route->getAction();

		if (in_array($method, $controller->getProtectedMethods()))
		{
			$action['protected'] = true;
		}
		elseif (in_array($method, $controller->getUnprotectedMethods()))
		{
			$action['protected'] = false;
		}

		$route->setAction($action);

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
		if ($this->requestTargettingApi($request))
		{
			list ($this->requestedVersion, $this->requestedFormat) = $this->parseAcceptHeader($request);

			try
			{
				$this->current = $route = $this->getApiRouteCollection($this->requestedVersion)->match($request);

				return $this->substituteBindings($route);
			}
			catch (NotFoundHttpException $exception)
			{
				// We won't do anything with the exception, we'll just gracefully fallback
				// to the default route collection to see if there's a match there.
			}
		}

		return parent::findRoute($request);
	}

	/**
	 * Determine if the current request is targetting an API.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return bool
	 */
	public function requestTargettingApi($request = null)
	{
		$request = $request ?: $this->currentRequest;

		if (empty($this->api))
		{
			return false;
		}

		$requestHash = $this->getRequestHash($request);

		if (isset($this->requestsTargettingApi[$requestHash]))
		{
			return $this->requestsTargettingApi[$requestHash];
		}

		if ($collection = $this->getApiRouteCollectionFromRequest($request))
		{
			try
			{
				$collection->match($request);

				return $this->requestsTargettingApi[$requestHash] = true;
			}
			catch (NotFoundHttpException $exception)
			{
				// If we don't find a matching route then we'll let this
				// fall through so that false is returned as the
				// request is not targetting the API.
			}
		}

		return $this->requestsTargettingApi[$requestHash] = false;
	}

	/**
	 * Parse a requests accept header.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function parseAcceptHeader($request)
	{
		$requestHash = $this->getRequestHash($request);

		if (isset($this->parsedAcceptHeaders[$requestHash]))
		{
			return $this->parsedAcceptHeaders[$requestHash];
		}

		if (preg_match('#application/vnd\.'.$this->vendor.'.(v[\d\.]+)\+(\w+)#', $request->header('accept'), $matches))
		{
			$parsed = array_slice($matches, 1);
		}
		else
		{
			$parsed = [$this->defaultVersion, $this->defaultFormat];
		}

		return $this->parsedAcceptHeaders[$requestHash] = $parsed;
	}

	/**
	 * Get a requests hash representation.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return string
	 */
	protected function getRequestHash($request)
	{
		return sha1($request);
	}

	/**
	 * Get a matching API route collection from the request.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return null|\Dingo\Api\Routing\ApiRouteCollection
	 */
	public function getApiRouteCollectionFromRequest(Request $request)
	{
		$collection = array_first($this->api, function($key, $collection) use ($request)
		{
			return $collection->matchesRequest($request);
		});

		// If we don't initially find a collection then we'll grab the default
		// version collection instead. This is a sort of graceful fallback
		// and allows viewing of the latest API version in the browser.
		if ( ! $collection)
		{
			return $this->getApiRouteCollection($this->defaultVersion);
		}

		return $collection;
	}

	/**
	 * Get an API route collection for a given version.
	 * 
	 * @param  string  $version
	 * @return \Dingo\Api\Routing\ApiRouteCollection
	 */
	public function getApiRouteCollection($version)
	{
		if ( ! isset($this->api[$version]))
		{
			throw new RuntimeException('There is no API route collection for the version "'.$version.'".');
		}

		return $this->api[$version];
	}

	/**
	 * Determine if a route is targetting the API.
	 * 
	 * @param  \Illuminate\Routing\Route
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

    public function getRoutes()
    {
        $routes = parent::getRoutes();

        foreach($this->api as $apiRoutes) {
            foreach($apiRoutes->getRoutes() as $apiRoute) {
                $routes->add($apiRoute);
            }
        }

        return $routes;
    }
}

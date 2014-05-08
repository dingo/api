<?php namespace Dingo\Api\Routing;

use Exception;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Illuminate\Routing\Route;
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
	 */
	public function api($options, callable $callback)
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
		catch (Exception $exception)
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
	 * @param  \Exception  $exception
	 * @return \Dingo\Api\Http\Response
	 * @throws \Exception
	 */
	public function handleException(Exception $exception)
	{
		// If the exception handler will handle the given exception then we'll fire
		// the callback registered to the handler and return the response.
		if ($this->exceptionHandler->willHandle($exception))
		{
			$response = $this->exceptionHandler->handle($exception);

			return Response::makeFromExisting($response);
		}
		elseif ( ! $exception instanceof HttpExceptionInterface)
		{
			throw $exception;
		}

		if ( ! $message = $exception->getMessage())
		{
			$message = sprintf('%d %s', $exception->getStatusCode(), Response::$statusTexts[$exception->getStatusCode()]);
		}

		$response = ['message' => $message];

		if ($exception instanceof ResourceException and $exception->hasErrors())
		{
			$response['errors'] = $exception->getErrors();
		}

		if ($code = $exception->getCode())
		{
			$response['code'] = $code;
		}

		return new Response($response, $exception->getStatusCode());
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

			$this->current = $route = $this->getApiRouteCollection($this->requestedVersion)->match($request);

			return $this->substituteBindings($route);
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

		if (isset($this->requestsTargettingApi[$key = sha1($request)]))
		{
			return $this->requestsTargettingApi[$key];
		}

		if ($collection = $this->getApiRouteCollectionFromRequest($request))
		{
			try
			{
				$collection->match($request);

				return $this->requestsTargettingApi[$key] = true;
			}
			catch (NotFoundHttpException $exception)
			{
				// No matching route so the request is not targetting API.
			}
		}

		return $this->requestsTargettingApi[$key] = false;
	}

	/**
	 * Parse a requests accept header.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function parseAcceptHeader($request)
	{
		if (preg_match('#application/vnd\.'.$this->vendor.'.(v[\d\.]+)\+(\w+)#', $request->header('accept'), $matches))
		{
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
}

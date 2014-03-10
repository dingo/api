<?php namespace Dingo\Api\Routing;

use Closure;
use Dingo\Api\Api;
use BadMethodCallException;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Router extends \Illuminate\Routing\Router {

	/**
	 * API instance.
	 * 
	 * @param \Dingo\Api\Api
	 */
	protected $api;

	/**
	 * Indicates if newly added routes are API routes.
	 * 
	 * @var bool
	 */
	protected $apiRouting = false;

	/**
	 * Register an API group.
	 * 
	 * @param  array  $options
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function api($options, Closure $callback)
	{
		$this->enableApiRouting();

		if ( ! isset($options['version']))
		{
			throw new BadMethodCallException('Unable to register API without an API version.');
		}

		// If the current request handles the version specified then we can go ahead and
		// register any routes for this API version. If not then we'll simply skip
		// the route registration for this version.
		if ($this->api->currentRequestHandlesVersion($options['version']))
		{
			unset($options['version']);

			$this->api->setRequestOptions($options) and $this->group($options, $callback);
		}

		$this->disableApiRouting();
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
		try
		{
			$response = parent::dispatch($request);
		}
		catch (HttpExceptionInterface $exception)
		{
			// If an exception is caught and we are currently routing an API request then
			// we'll handle this exception by building a new response from it. This
			// allows the API to gracefully handle its own exceptions.
			if ($this->routingForApi() and ! $request instanceof InternalRequest)
			{
				$response = $this->api->handleException($exception);
			}
			else
			{
				throw $exception;
			}
		}

		if ($this->routingForApi())
		{
			$response = Response::makeFromExisting($response)->morph();

			// If the request that was dispatched is an internal request then we need to
			// disable the API routing so that the parent request is not treated in
			// the same way. This prevents it from generating an Api Response for
			// the parent request. Another internal request will still result
			// in API routing being enabled.
			if ($request instanceof InternalRequest)
			{
				$this->disableApiRouting();
			}
		}

		return $response;
	}

	/**
	 * Add a route to the underlying route collection.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string  $action
	 * @return \Illuminate\Routing\Route
	 */
	protected function addRoute($methods, $uri, $action)
	{
		$route = parent::addRoute($methods, $uri, $action);

		// If the router is currently routing as an API then we'll attach the API before filter
		// that will ensure routes are treated as API requests.
		if ($this->routingForApi())
		{
			$route->before('api');
		}

		return $route;
	}

	/**
	 * Determine if the current request is an API request.
	 * 
	 * @return bool
	 */
	public function routingForApi()
	{
		return $this->apiRouting or $this->api->currentRequestTargettingApi();
	}

	/**
	 * Enable API request.
	 * 
	 * @return \Dingo\Api\Routing\Router
	 */
	public function enableApiRouting()
	{
		$this->apiRouting = true;

		return $this;
	}

	/**
	 * Disable API request.
	 * 
	 * @return \Dingo\Api\Routing\Router
	 */
	public function disableApiRouting()
	{
		$this->apiRouting = false;

		return $this;
	}

	/**
	 * Set the current request instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function setApi(Api $api)
	{
		$this->api = $api;
	}

}
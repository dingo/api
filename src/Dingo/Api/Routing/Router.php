<?php namespace Dingo\Api\Routing;

use Closure;
use Exception;
use Dingo\Api\ApiException;
use Illuminate\Http\Request;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\Response as ApiResponse;
use Illuminate\Routing\Router as IlluminateRouter;

class Router extends IlluminateRouter {

	/**
	 * Indicates if newly added routes are API routes.
	 * 
	 * @var bool
	 */
	protected $api = false;

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
		catch (ApiException $exception)
		{
			// If we catch an exception and we are routing for the API then we'll handle
			// the exception, otherwise we don't want to interfere with the exception
			// handling.
			if ($this->routingForApi())
			{
				$response = $this->handleResponseException($exception);
			}
			else
			{
				throw $exception;
			}
		}

		// If the current request is being treated as an API request then we'll return a
		// new instance of \Dingo\Api\Http\Response.
		if ($this->routingForApi())
		{
			$response = ApiResponse::makeFromExisting($response)->process();

			// If the request is an internal request then we'll disable the API now as
			// well as it will cause the parent request to generate an ApiResponse.
			if ($request instanceof InternalRequest)
			{
				$this->disableApi();
			}
		}

		return $response;
	}

	/**
	 * Handle an ApiException thrown when fetching the response.
	 * 
	 * @param  \Dingo\Api\ApiException  $exception
	 * @return \Dingo\Api\Http\Response
	 */
	protected function handleResponseException(ApiException $exception)
	{
		$errors = $exception->getErrors();

		// If we have errors then we'll return an array as our response here with both
		// the message and the errors, that way our dispatcher can re-throw the
		// exception when it gets this response.
		if ( ! $errors->isEmpty())
		{
			return new ApiResponse([$exception->getMessage(), $errors], $exception->getStatusCode());
		}

		return new ApiResponse($exception->getMessage(), $exception->getStatusCode());
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
		return $this->api;
	}

	/**
	 * Enable API request.
	 * 
	 * @return \Dingo\Api\Routing\Router
	 */
	public function enableApi()
	{
		$this->api = true;

		return $this;
	}

	/**
	 * Disable API request.
	 * 
	 * @return \Dingo\Api\Routing\Router
	 */
	public function disableApi()
	{
		$this->api = false;

		return $this;
	}

}
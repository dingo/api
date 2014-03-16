<?php namespace Dingo\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Dispatcher {

	/**
	 * Illuminate request instance.
	 * 
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * API router instance.
	 * 
	 * @var \Dingo\Api\Routing\Router
	 */
	protected $router;

	/**
	 * Original request input array.
	 * 
	 * @var array
	 */
	protected $originalRequestInput = [];

	/**
	 * Internal request stack.
	 * 
	 * @var array
	 */
	protected $requestStack = [];

	/**
	 * Version for the request.
	 * 
	 * @var string
	 */
	protected $version;

	/**
	 * Request parameters.
	 * 
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Create a new dispatcher instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Dingo\Api\Routing\Router  $router
	 * @param  \Dingo\Api\Api  $api
	 * @return void
	 */
	public function __construct(Request $request, Router $router)
	{
		$this->request = $request;
		$this->router = $router;
	}

	/**
	 * Set the version of the API for the next request.
	 * 
	 * @param  string  $version
	 * @return \Dingo\Api\Dispatcher
	 */
	public function version($version)
	{
		$this->version = $version;

		return $this;
	}

	/**
	 * Set the parameters to be sent on the next API request.
	 * 
	 * @param  array  $parameters
	 * @return \Dingo\Api\Dispatcher
	 */
	public function with(array $parameters)
	{
		$this->parameters = $parameters;

		return $this;
	}

	/**
	 * Perform API GET request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function get($uri, $parameters = [])
	{
		return $this->queueRequest('get', $uri, $parameters);
	}

	/**
	 * Perform API POST request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function post($uri, $parameters = [])
	{
		return $this->queueRequest('post', $uri, $parameters);
	}

	/**
	 * Perform API PUT request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function put($uri, $parameters = [])
	{
		return $this->queueRequest('put', $uri, $parameters);
	}

	/**
	 * Perform API PATCH request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function patch($uri, $parameters = [])
	{
		return $this->queueRequest('patch', $uri, $parameters);
	}

	/**
	 * Perform API HEAD request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function head($uri, $parameters = [])
	{
		return $this->queueRequest('head', $uri, $parameters);
	}

	/**
	 * Perform API DELETE request.
	 * 
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function delete($uri, $parameters = [])
	{
		return $this->queueRequest('delete', $uri, $parameters);
	}

	/**
	 * Queue up and dispatch a new request.
	 * 
	 * @param  string  $verb
	 * @param  string  $uri
	 * @param  array  $parameters
	 * @return mixed
	 */
	protected function queueRequest($verb, $uri, $parameters)
	{
		$identifier = $this->buildRequestIdentifier($verb, $uri);

		$this->requestStack[$identifier] = $this->createRequest($verb, $uri, $parameters);

		$this->request->replace($this->requestStack[$identifier]->input());

		return $this->dispatch($this->requestStack[$identifier]);
	}

	/**
	 * Create a new internal request from an HTTP verb and URI.
	 * 
	 * @param  string  $verb
	 * @param  string  $uri
	 * @return \Dingo\Api\Http\InternalRequest
	 */
	protected function createRequest($verb, $uri, $parameters)
	{
		foreach (['cookies', 'files', 'server'] as $parameter)
		{
			${$parameter} = $this->request->{$parameter}->all();
		}

		if ( ! isset($this->version))
		{
			$this->version = $this->router->getDefaultApiVersion();
		}

		// Once we have a version we can go ahead and grab the API collection,
		// if one exists, from the router.
		$api = $this->router->getApiCollection($this->version);

		if ($prefix = $api->option('prefix'))
		{
			$uri = "{$prefix}/{$uri}";
		}

		$parameters = array_merge($this->parameters, $parameters);

		$request = InternalRequest::create($uri, $verb, $parameters, $cookies, $files, $server);

		if ($domain = $api->option('domain'))
		{
			$request->headers->set('host', $domain);
		}

		$request->headers->set('accept', $this->buildAcceptHeader());

		return $request;
	}

	/**
	 * Build the "Accept" header.
	 * 
	 * @return string
	 */
	protected function buildAcceptHeader()
	{
		return "application/vnd.{$this->router->getApiVendor()}.{$this->version}+json";
	}

	/**
	 * Attempt to dispatch an internal request.
	 * 
	 * @param  \Dingo\Api\Http\InternalRequest  $request
	 * @return mixed
	 */
	protected function dispatch(InternalRequest $request)
	{
		try
		{
			$this->router->enableApiRouting();

			$response = $this->router->dispatch($request);

			if ( ! $response->isOk())
			{
				throw new HttpException($response->getStatusCode(), $response->getOriginalContent());
			}
		}
		catch (HttpExceptionInterface $exception)
		{
			$this->refreshRequestStack();

			throw $exception;
		}

		$this->refreshRequestStack();

		return $response->getOriginalContent();
	}

	/**
	 * Refresh the request stack by popping the last request from the stack,
	 * replacing the input, and resetting the version and parameters.
	 * 
	 * @return void
	 */
	protected function refreshRequestStack()
	{
		array_pop($this->requestStack);

		$this->request->replace($this->originalRequestInput);

		$this->version = null;

		$this->parameters = [];
	}

	/**
	 * Build a request identifier by joining the verb and URI together.
	 * 
	 * @param  string  $verb
	 * @param  string  $uri
	 * @return string
	 */
	protected function buildRequestIdentifier($verb, $uri)
	{
		return "{$verb} {$uri}";
	}

}
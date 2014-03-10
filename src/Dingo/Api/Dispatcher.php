<?php namespace Dingo\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;
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
	 * The API instance.
	 * 
	 * @var \Dingo\Api\Api
	 */
	protected $api;

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
	public function __construct(Request $request, Router $router, Api $api)
	{
		$this->request = $request;
		$this->router = $router;
		$this->api = $api;
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
	 * @return mixed
	 */
	public function get($uri)
	{
		return $this->queueRequest('get', $uri);
	}

	/**
	 * Perform API POST request.
	 * 
	 * @param  string  $uri
	 * @return mixed
	 */
	public function post($uri)
	{
		return $this->queueRequest('post', $uri);
	}

	/**
	 * Perform API PUT request.
	 * 
	 * @param  string  $uri
	 * @return mixed
	 */
	public function put($uri)
	{
		return $this->queueRequest('put', $uri);
	}

	/**
	 * Perform API PATCH request.
	 * 
	 * @param  string  $uri
	 * @return mixed
	 */
	public function patch($uri)
	{
		return $this->queueRequest('patch', $uri);
	}

	/**
	 * Perform API HEAD request.
	 * 
	 * @param  string  $uri
	 * @return mixed
	 */
	public function head($uri)
	{
		return $this->queueRequest('head', $uri);
	}

	/**
	 * Perform API DELETE request.
	 * 
	 * @param  string  $uri
	 * @return mixed
	 */
	public function delete($uri)
	{
		return $this->queueRequest('delete', $uri);
	}

	/**
	 * Queue up and dispatch a new request.
	 * 
	 * @param  string  $verb
	 * @param  string  $uri
	 * @return mixed
	 */
	protected function queueRequest($verb, $uri)
	{
		$identifier = $this->buildRequestIdentifier($verb, $uri);

		$this->requestStack[$identifier] = $this->createRequest($verb, $uri);

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
	protected function createRequest($verb, $uri)
	{
		foreach (['cookies', 'files', 'server'] as $parameter)
		{
			${$parameter} = $this->request->{$parameter}->all();
		}

		if ($this->api->hasPrefix())
		{
			$uri = "{$this->api->getPrefix()}/{$uri}";
		}

		$request = InternalRequest::create($uri, $verb, $this->parameters, $cookies, $files, $server);

		if ($this->api->hasDomain())
		{
			$request->headers->set('host', $this->api->getDomain());
		}

		if ( ! isset($this->version))
		{
			$this->version = $this->api->getDefaultVersion();
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
		return "application/vnd.{$this->api->getVendor()}.{$this->version}+json";
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
			$response = $this->router->dispatch($request);
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
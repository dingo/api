<?php namespace Dingo\Api;

use Closure;
use RuntimeException;
use Dingo\Api\Auth\Shield;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Auth\GenericUser;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
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
	 * Illuminate url generator instance.
	 * 
	 * @var \Illuminate\Routing\UrlGenerator
	 */
	protected $url;

	/**
	 * API router instance.
	 * 
	 * @var \Dingo\Api\Routing\Router
	 */
	protected $router;

	/**
	 * API authentication shield instance.
	 * 
	 * @var \Dingo\Api\Auth\Shield
	 */
	protected $shield;

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
	 * Indicates whether the authenticated user is persisted.
	 * 
	 * @var bool
	 */
	protected $persistAuthenticationUser = true;

	/**
	 * Create a new dispatcher instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\UrlGenerator  $url
	 * @param  \Dingo\Api\Routing\Router  $router
	 * @param  \Dingo\Api\Auth\Shield  $shield
	 * @return void
	 */
	public function __construct(Request $request, UrlGenerator $url, Router $router, Shield $shield)
	{
		$this->request = $request;
		$this->url = $url;
		$this->router = $router;
		$this->shield = $shield;
	}

	/**
	 * Internal request will be authenticated as the given user.
	 * 
	 * @param  \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model  $user
	 * @return \Dingo\Api\Dispatcher
	 */
	public function be($user)
	{
		if ( ! $user instanceof Model and ! $user instanceof GenericUser)
		{
			throw new RuntimeException('User must be an instance of either Illuminate\Database\Eloquent\Model or Illuminate\Auth\GenericUser.');
		}

		$this->shield->setUser($user);

		return $this;
	}

	/**
	 * Only authenticate with the given user for a single request.
	 * 
	 * @return \Dingo\Api\Dispatcher
	 */
	public function once()
	{
		$this->persistAuthenticationUser = false;

		return $this;
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
	 * @param  string|array  $parameters
	 * @return \Dingo\Api\Dispatcher
	 */
	public function with($parameters)
	{
		$this->parameters = is_array($parameters) ? $parameters : func_get_args();

		return $this;
	}

	/**
	 * Perform an API request to a named route.
	 * 
	 * @param  string  $name
	 * @param  string|array  $routeParameters
	 * @param  string|array  $parameters
	 * @return mixed
	 */
	public function route($name, $routeParameters = [], $parameters = [])
	{
		$version = $this->version ?: $this->router->getDefaultVersion();

		$route = $this->router->getApiRouteCollection($version)->getByName($name);

		$uri = ltrim($this->url->route($name, $routeParameters, false, $route), '/');

		return $this->queueRequest($route->methods()[0], $uri, $parameters);
	}

	/**
	 * Perform an API request to a controller action.
	 * 
	 * @param  string  $name
	 * @param  string|array  $actionParameters
	 * @param  string|array  $parameters
	 * @return mixed
	 */
	public function action($action, $actionParameters = [], $parameters = [])
	{
		$version = $this->version ?: $this->router->getDefaultVersion();

		$route = $this->router->getApiRouteCollection($version)->getByAction($action);

		$uri = ltrim($this->url->route($action, $actionParameters, false, $route), '/');

		return $this->queueRequest($route->methods()[0], $uri, $parameters);
	}

	/**
	 * Perform API GET request.
	 * 
	 * @param  string  $uri
	 * @param  string|array  $parameters
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
	 * @param  string|array  $parameters
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
	 * @param  string|array  $parameters
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
	 * @param  string|array  $parameters
	 * @return mixed
	 */
	public function patch($uri, $parameters = [])
	{
		return $this->queueRequest('patch', $uri, $parameters);
	}

	/**
	 * Perform API DELETE request.
	 * 
	 * @param  string  $uri
	 * @param  string|array  $parameters
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
	 * @param  string|array  $parameters
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
	 * @param  string|array  $parameters
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
			$this->version = $this->router->getDefaultVersion();
		}

		// Once we have a version we can go ahead and grab the API collection,
		// if one exists, from the router.
		$api = $this->router->getApiRouteCollection($this->version);

		if ($prefix = $api->option('prefix') and ! starts_with($uri, $prefix))
		{
			$uri = "{$prefix}/{$uri}";
		}

		$parameters = array_merge($this->parameters, (array) $parameters);

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
		return 'application/vnd'.$this->router->getVendor().'.'.$this->version.'+json';
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
	 * Refresh the request stack by resetting the authentication,
	 * popping the last request from the stack, replacing the
	 * input, and resetting the version and parameters.
	 * 
	 * @return void
	 */
	protected function refreshRequestStack()
	{
		if ( ! $this->persistAuthenticationUser)
		{
			$this->shield->setUser(null);

			$this->persistAuthenticationUser = true;
		}

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
		return $verb.' '.$uri;
	}

}

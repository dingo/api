<?php namespace Dingo\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
	 * The API vendor.
	 * 
	 * @var string
	 */
	protected $vendor;

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
	 * Indicates the default API version.
	 * 
	 * @var string
	 */
	protected $defaultVersion;

	/**
	 * API domain.
	 * 
	 * @var string
	 */
	protected $domain;

	/**
	 * API prefix.
	 * 
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new dispatcher instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Dingo\Api\Routing\Router  $router
	 * @param  string  $vendor
	 * @return void
	 */
	public function __construct(Request $request, Router $router, $vendor)
	{
		$this->request = $request;
		$this->router = $router;
		$this->vendor = $vendor;
	}

	/**
	 * Set the API domain.
	 * 
	 * @param  string  $domain
	 * @return \Dingo\Api\Dispatcher
	 */
	public function domain($domain)
	{
		$this->domain = $domain;

		return $this;
	}

	/**
	 * Set the API prefix.
	 * 
	 * @param  string  $prefix
	 * @return \Dingo\Api\Dispatcher
	 */
	public function prefix($prefix)
	{
		$this->prefix = $prefix;

		return $this;
	}

	/**
	 * Set the default API version.
	 * 
	 * @param  string  $version
	 * @return \Dingo\Api\Dispatcher
	 */
	public function defaultsTo($version)
	{
		$this->defaultVersion = $version;

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

		// If a prefix was set for the API then we'll prefix our URI.
		if ($this->prefix)
		{
			$uri = "{$this->prefix}/{$uri}";
		}

		$request = InternalRequest::create($uri, $verb, $this->parameters, $cookies, $files, $server);

		// If  adomain was set for the API then we'll set the host header.
		if ($this->domain)
		{
			$request->headers->set('host', $this->domain);
		}

		// If no version was explicitly set for this request then we'll grab the default
		// API version from the API router.
		if ( ! $this->version)
		{
			$this->version = $this->defaultVersion;
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
		return "application/vnd.{$this->vendor}.{$this->version}+json";
	}

	/**
	 * Attempt to dispatch an internal request to the API.
	 * 
	 * @param  \Dingo\Api\Http\InternalRequest  $request
	 * @return mixed
	 */
	protected function dispatch(InternalRequest $request)
	{
		try
		{
			$response = $this->router->dispatch($request);

			// If we did not get an OK response then we'll throw an ApiException and
			// then catch it next. If the original content given to the response
			// is an array then we'll treat this as a conventional
			// message/errors array and throw them with the
			// new exception.
			if ( ! $response->isOk())
			{
				$original = $response->getOriginalContent();

				$message = $errors = null;

				if (is_array($original))
				{
					foreach (['message', 'errors'] as $key)
					{
						if (array_key_exists($key, $original)) ${$key} = $original[$key];
					}
				}

				throw new ApiException($response->getStatusCode(), $message, $errors);
			}
		}
		catch (HttpException $exception)
		{
			$statusCode = $exception->getStatusCode();

			if ( ! $message = $exception->getMessage())
			{
				$message = sprintf('%d %s', $statusCode, Response::$statusTexts[$statusCode]);
			}

			// If our exception is already an instance of ApiException we'll grab the
			// errors from it as well.
			$errors = ($exception instanceof ApiException) ? $exception->getErrors() : null;

			throw new ApiException($statusCode, $message, $errors, $exception->getPrevious(), $exception->getHeaders(), $exception->getCode());
		}

		// We can now pop the last request of the request stack and reset the original
		// input back to what it was.
		array_pop($this->requestStack);

		$this->request->replace($this->originalRequestInput);

		// Unset the parameters and the version that were sent for this request.
		$this->version = null;

		$this->parameters = [];

		return $response->getOriginalContent();
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

	/**
	 * Group routes for a given API version.
	 * 
	 * @param  string|array  $version
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function group($version, Closure $callback)
	{
		$this->router->enableApi();

		// If the current request handles the version specified then we can go ahead and
		// register any routes for this API version. If not then we'll simply skip
		// the route registration for this version.
		if ($this->requestHandlesVersion($version))
		{
			$options = [];

			foreach (['domain', 'prefix'] as $option)
			{
				if ($this->{$option}) $options[$option] = $this->{$option};
			}

			$this->router->group($options, $callback);
		}

		$this->router->disableApi();
	}

	/**
	 * Determine if the current request will handle the version specified.
	 * 
	 * @param  string  $version
	 * @return bool
	 */
	protected function requestHandlesVersion($version)
	{
		$versions = (array) $version;

		// Attempt to parse the version from the requests Accept header using a
		// simple regular expression.
		$accept = $this->request->header('accept');

		preg_match('#application/vnd\.'.$this->vendor.'.(v\d)\+(json)#', $accept, $matches);

		if ( ! empty($matches))
		{
			list ($accept, $requestedVersion, $requestedFormat) = $matches;

			return in_array($requestedVersion, $versions);
		}

		// If we didn't get any matches then we need to check if the version we were
		// given matches the default version.
		else
		{
			return in_array($this->defaultVersion, $versions);
		}
	}

}
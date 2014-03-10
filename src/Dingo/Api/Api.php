<?php namespace Dingo\Api;

use Closure;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Api {

	/**
	 * Illuminate request instance.
	 * 
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The API vendor.
	 * 
	 * @var string
	 */
	protected $vendor;

	/**
	 * Indicates the default API version.
	 * 
	 * @var string
	 */
	protected $defaultVersion;

	/**
	 * Domain for current API request.
	 * 
	 * @var string
	 */
	protected $domain;

	/**
	 * Prefix for current API request.
	 * 
	 * @var string
	 */
	protected $prefix;

	/**
	 * Create a new dispatcher instance.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Dingo\Api\ExceptionHandler $exceptionHandler
	 * @param  string  $vendor
	 * @param  string  $defaultVersion
	 * @return void
	 */
	public function __construct(Request $request, ExceptionHandler $exceptionHandler, $vendor, $defaultVersion)
	{
		$this->request = $request;
		$this->exceptionHandler = $exceptionHandler;
		$this->vendor = $vendor;
		$this->defaultVersion = $defaultVersion;
	}

	/**
	 * Determine if the current request will handle the version specified.
	 * 
	 * @param  string  $version
	 * @return bool
	 */
	public function currentRequestHandlesVersion($version)
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

	/**
	 * Determine if the current request is targetting the API.
	 * 
	 * @return bool
	 */
	public function currentRequestTargettingApi()
	{
		if ($this->request->header('host') == $this->domain)
		{
			return true;
		}
		elseif (preg_match('#^/'.$this->prefix.'(/?.*?)#', $this->request->getPathInfo()))
		{
			return true;
		}

		return false;
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
			$message = [$message, $exception->errors()];
		}

		return new Response($message, $exception->getStatusCode());
	}

	/**
	 * Set the current request options.
	 * 
	 * @param  array  $options
	 * @return \Dingo\Api\Api
	 */
	public function setRequestOptions(array $options)
	{
		foreach (['prefix', 'domain'] as $option)
		{
			if (array_key_exists($option, $options)) $this->{$option} = $options[$option];
		}

		return $this;
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
	 * Get the default API version.
	 * 
	 * @return string
	 */
	public function getDefaultVersion()
	{
		return $this->defaultVersion;
	}

	/**
	 * Determine if current API request has a prefix.
	 * 
	 * @return bool
	 */
	public function hasPrefix()
	{
		return isset($this->prefix);
	}

	/**
	 * Determine if current API request has a domain.
	 * 
	 * @return bool
	 */
	public function hasDomain()
	{
		return isset($this->domain);
	}

	/**
	 * Get the API request prefix.
	 * 
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Get the API request domain.
	 * 
	 * @return string
	 */
	public function getDomain()
	{
		return $this->domain;
	}

}
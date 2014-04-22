<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Dingo\OAuth2\Server\Resource;
use Dingo\OAuth2\Exception\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuth2Provider extends AuthorizationProvider {

	/**
	 * OAuth 2.0 resource server instance.
	 * 
	 * @var \Dingo\OAuth2\Server\Resource
	 */
	protected $resource;

	/**
	 * Array of request scopes.
	 * 
	 * @var array
	 */
	protected $scopes = [];

	/**
	 * Create a new Dingo\Api\Auth\OAuth2Provider instance.
	 * 
	 * @param  \Dingo\OAuth2\Server\Resource  $resource
	 * @param  array  $options
	 * @return void
	 */
	public function __construct(Resource $resource, array $options)
	{
		$this->resource = $resource;
		$this->options = $options;
	}

	/**
	 * Authenticate request with OAuth2.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return int
	 */
	public function authenticate(Request $request)
	{
		try
		{
			$this->validateAuthorizationHeader($request);
		}
		catch (Exception $exception)
		{
			// If we catch an exception here it means the header was missing so we'll
			// now look for the access token in the query string. If we don't have
			// the query string either then we'll re-throw the exception.
			if ( ! $request->query('access_token', false))
			{
				throw $exception;
			}
		}

		try
		{
			$token = $this->resource->validateRequest($this->scopes);

			return $token->getUserId();
		}
		catch (InvalidTokenException $exception)
		{
			throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
		}
	}

	/**
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	public function getAuthorizationMethod()
	{
		return 'bearer';
	}

	/**
	 * Set the OAuth 2.0 request scopes.
	 * 
	 * @param  array  $scopes
	 * @return \Dingo\Api\Auth\OAuth2Provider
	 */
	public function setScopes(array $scopes)
	{
		$this->scopes = $scopes;

		return $this;
	}

}

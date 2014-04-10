<?php namespace Dingo\Api\Auth;

use Dingo\OAuth2\Server\Resource;
use Dingo\OAuth2\Exception\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuth2Provider extends Provider {

	/**
	 * Create a new Dingo\Api\Auth\OAuth2Provider instance.
	 * 
	 * @param  \Illuminate\Auth\Guard  $auth
	 * @param  \Dingo\OAuth2\Server\Resource  $resource
	 * @return void
	 */
	public function __construct(Resource $resource)
	{
		$this->resource = $resource;
	}

	/**
	 * Authenticate request with OAuth2.
	 * 
	 * @param  array  $scopes
	 * @return int
	 */
	public function authenticate(array $scopes)
	{
		try
		{
			$token = $this->resource->validateRequest($scopes);

			return $token->getUserId();
		}
		catch (InvalidTokenException $exception)
		{
			throw new UnauthorizedHttpException('Bearer', $exception->getMessage());
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

}
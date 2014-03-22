<?php namespace Dingo\Api\Auth;

use Illuminate\Auth\AuthManager;
use League\OAuth2\Server\Resource;
use League\Oauth2\Server\Exception\InvalidAccessTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuthProvider implements ProviderInterface {

	/**
	 * Create a new Dingo\Api\Auth\OAuthProvider instance.
	 * 
	 * @param  \Illuminate\Auth\AuthManager  $auth
	 * @param  \League\OAuth2\Server\Resource  $resource
	 * @return void
	 */
	public function __construct(AuthManager $auth, Resource $resource)
	{
		$this->auth = $auth;
		$this->resource = $resource;
	}

	/**
	 * Authenticate request with OAuth2.
	 * 
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Auth\GenericUser
	 */
	public function authenticate()
	{
		try
		{
			$this->resource->isValid();

			$this->auth->onceUsingId($this->resource->getOwnerId());

			return $this->auth->getUser();
		}
		catch (InvalidAccessTokenException $exception)
		{
			throw new UnauthorizedHttpException('Bearer', 'Access token was missing or is invalid.');
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
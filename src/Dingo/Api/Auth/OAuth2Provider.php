<?php namespace Dingo\Api\Auth;

use Illuminate\Auth\AuthManager;
use Dingo\OAuth2\Server\Resource;
use Dingo\OAuth2\Exception\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuth2Provider implements ProviderInterface {

	/**
	 * Create a new Dingo\Api\Auth\OAuth2Provider instance.
	 * 
	 * @param  \Illuminate\Auth\AuthManager  $auth
	 * @param  \Dingo\OAuth2\Server\Resource  $resource
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
			$token = $this->resource->validate();

			$this->auth->onceUsingId($token->getUserId());

			return $this->auth->getUser();
		}
		catch (InvalidTokenException $exception)
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
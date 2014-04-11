<?php namespace Dingo\Api\Auth;

use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicProvider extends Provider {

	/**
	 * Create a new Dingo\Api\Auth\BasicProvider instance.
	 * 
	 * @param  \Illuminate\Auth\AuthManager  $auth
	 * @return void
	 */
	public function __construct(AuthManager $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Authenticate request with Basic.
	 * 
	 * @param  array  $scopes
	 * @return int
	 */
	public function authenticate(array $scopes)
	{
		if ($response = $this->auth->onceBasic() and $response->getStatusCode() === 401)
		{
			throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
		}

		return $this->auth->user()->id;
	}

	/**
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	public function getAuthorizationMethod()
	{
		return 'basic';
	}

}
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
	public function __construct(AuthManager $auth, $username_column)
	{
		$this->auth = $auth;
		$this->username_column =  $username_column;
	}

	/**
	 * Authenticate request with Basic.
	 * 
	 * @param  array  $scopes
	 * @return int
	 */
	public function authenticate(array $scopes)
	{
		if ($response = $this->auth->onceBasic($this->username_column) and $response->getStatusCode() === 401)
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
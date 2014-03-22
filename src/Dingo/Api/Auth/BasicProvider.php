<?php namespace Dingo\Api\Auth;

use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicProvider implements ProviderInterface {

	/**
	 * Create a new Dingo\Api\Auth\BasicProvider instance.
	 * 
	 * @param  \Illuminate\auth\AuthManager  $auth
	 * @return void
	 */
	public function __construct(AuthManager $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Authenticate request with Basic.
	 * 
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Auth\GenericUser
	 */
	public function authenticate()
	{
		if ($response = $this->auth->onceBasic() and $response->getStatusCode() === 401)
		{
			throw new UnauthorizedHttpException('Basic', 'Invalid credentials.');
		}

		return $this->auth->getUser();
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
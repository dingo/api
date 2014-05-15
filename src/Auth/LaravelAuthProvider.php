<?php namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LaravelAuthProvider extends AuthorizationProvider {

	/**
	 * Illuminate authentication manager.
	 * 
	 * @var \Illuminate\Auth\AuthManager
	 */
	protected $auth;

	/**
	 * Create a new instance.
	 * 
	 * @param  \Illuminate\Auth\AuthManager  $auth
	 * @return void
	 */
	public function __construct(AuthManager $auth)
	{
		$this->auth = $auth;
	}

	/**
	 * Check if user is authenticated.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\Route  $route
	 * @return int
	 */
	public function authenticate(Request $request, Route $route)
	{
		if($this->auth->check())
		{
			return $this->auth->user()->id;
		}
		
		throw new UnauthorizedHttpException('Laravel', 'Not authorized.');
	}

	/**
	 * Get the providers authorization method.
	 * 
	 * @return string
	 */
	public function getAuthorizationMethod()
	{
		return 'laravel';
	}

}

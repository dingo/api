<?php namespace Dingo\Api;

use Exception;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthManager;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authentication {

	/**
	 * Authenticated user.
	 * 
	 * @var \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	protected $user;

    /**
     * Create a new Dingo\Api\Authentication instance.
     * 
     * @param  \Dingo\Api\Routing\Router  $router
     * @param  \Illuminate\Auth\AuthManager  $auth
     * @param  array  $providers
     * @return void
     */
	public function __construct(Router $router, AuthManager $auth, array $providers)
	{
		$this->router = $router;
		$this->auth = $auth;
		$this->providers = $providers;
	}

	/**
	 * Authenticate the current request.
	 * 
	 * @return null|\Dingo\Api\Http\Response
	 */
	public function authenticate()
	{
		$request = $this->router->getCurrentRequest();

		if ( ! $this->router->routingForApi() or $request instanceof InternalRequest)
		{
			return;
		}

		if ($route = $this->router->getCurrentRoute() and $this->routeIsProtected($route))
		{
			$exceptionStack = [];

			foreach ($this->providers as $provider)
			{
				try
				{
					$provider->validateAuthorizationHeader($request);

					return $this->userId = $provider->authenticate();
				}
				catch (UnauthorizedHttpException $exception)
				{
					$exceptionStack[] = $exception;
				}
				catch (Exception $exception)
				{
					// We won't add this exception to the stack as it's thrown when the provider
					// is unable to authenticate due to the correct authorization header not
					// being set. We will throw an exception for this below.
				}
			}

			$exception = array_shift($exceptionStack);

			if ($exception === null)
			{
				$exception = new UnauthorizedHttpException(null, 'Failed to authenticate because of an invalid or missing authorization header.');
			}

			throw $exception;
		}
	}

	/**
	 * Determine if a route is protected.
	 * 
	 * @param  \Illuminate\Routing\Route  $route
	 * @return bool
	 */
	protected function routeIsProtected(Route $route)
	{
		$actions = $route->getAction();

		return isset($actions['protected']) and $actions['protected'] === true;
	}

	/**
	 * Get the authenticated user.
	 * 
	 * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	public function getUser()
	{
		if ( ! $this->auth->check())
		{
			$this->auth->onceUsingId($this->userId);
		}

		return $this->auth->user();
	}

	/**
	 * Alias for getUser.
	 * 
	 * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	public function user()
	{
		return $this->getUser();
	}

}
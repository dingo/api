<?php namespace Dingo\Api;

use Exception;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Routing\Route;
use Dingo\Api\Auth\AuthManager;
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
     * @param  \Dingo\Api\Auth\AuthManager  $auth
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
					return $this->user = $this->auth->driver($provider)->authenticate($request);
				}
				catch (UnauthorizedHttpException $exception)
				{
					$exceptionStack[] = $exception;
				}
				catch (Exception $exception)
				{
					// We won't add this exception to the stack as it's thrown when the provider
					// is unable to authenticate due to the correct authorization header not
					// being set.
				}
			}

			$exception = array_shift($exceptionStack);

			if ($exception === null)
			{
				$exception = new UnauthorizedHttpException(null, 'Authentication required.');
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
		return $this->user;
	}

}
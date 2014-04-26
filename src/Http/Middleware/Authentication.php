<?php namespace Dingo\Api\Http\Middleware;

use Dingo\Api\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authentication implements HttpKernelInterface {

	/**
	 * The wrapped kernel implementation.
	 * 
	 * @var \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	protected $app;

	/**
	 * Create a new authentication middleware instance.
	 * 
	 * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
	 * @return void
	 */
	public function __construct(HttpKernelInterface $app)
	{
		$this->app = $app;
	}

	/**
	 * Handle a given request and return the response.
	 * 
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  int  $type
	 * @param  bool  $catch
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		// Our middleware needs to ensure that Laravel is booted before we
		// can do anything. This gives us access to all the booted
		// service providers and other container bindings.
		$this->app->boot();

		if ($request instanceof InternalRequest or $this->app->make('dingo.api.auth')->user())
		{
			return $this->app->handle($request, $type, $catch);
		}

		$router = $this->app->make('router');

		$response = null;

		// If a collection exists for the request and we can match a route
		// from the request then we'll check to see if the route is
		// protected and, if it is, we'll attempt to authenticate.
		if ($collection = $router->getApiRouteCollectionFromRequest($request) and $route = $collection->match($request))
		{
			if ($this->routeIsProtected($route))
			{
				$response = $this->authenticate($request, $route);
			}	
		}

		return $response ?: $this->app->handle($request, $type, $catch);
	}

	/**
	 * Authenticate the request for the given route.
	 * 
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Illuminate\Routing\Route  $route
	 * @return null|\Dingo\Api\Http\Response
	 * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
	 */
	protected function authenticate(Request $request, Route $route)
	{
		try
		{
			$this->app->make('dingo.api.auth')->authenticate($request, $route);
		}
		catch (UnauthorizedHttpException $exception)
		{
			$router = $this->app->make('router');

			$response = $router->handleException($exception);

			return Response::makeFromExisting($response)->morph($router->getRequestedFormat());
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
		$action = $route->getAction();

		return in_array('protected', $action, true) or (isset($action['protected']) and $action['protected'] === true);
	}

}

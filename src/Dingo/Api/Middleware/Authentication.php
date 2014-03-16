<?php namespace Dingo\Api\Middleware;

use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use League\OAuth2\Server\Exception\InvalidAccessTokenException;

class Authentication implements HttpKernelInterface {

	/**
	 * Create a new \Dingo\Api\Middleware\Authentication instance.
	 * 
	 * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
	 * @return void
	 */
	public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the given request and get the response.
     * 
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  int  $type
     * @param  bool  $catch
     * @return \Symfony\Component\HttpFoundation\Response
     */
	public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		$response = $this->app->handle($request, $type, $catch);

		if ($route = $this->app['router']->current())
		{
			$actions = $route->getAction();

			// If we are routing for the API and the current route is marked as protected then
			// we'll ensure that a valid access token was sent along.
			if ($this->app['router']->routingForApi() and isset($actions['protected']) and $actions['protected'] === true)
			{
				try
				{
					$this->app['dingo.oauth.resource']->isValid();
				}
				catch (InvalidAccessTokenException $exception)
				{
					$response = new Response('Access token was missing or is invalid.', 403);

					$response->morph();
				}
			}
		}

		return $response;
	}

}
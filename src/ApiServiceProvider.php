<?php namespace Dingo\Api;

use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\AuthManager;
use Dingo\Api\Auth\BasicProvider;
use Dingo\Api\Auth\OAuth2Provider;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ApiServiceProvider extends ServiceProvider {

	/**
	 * Boot the service provider.
	 * 
	 * @return void
	 */
	public function boot()
	{
		$this->package('dingo/api', 'api', __DIR__);

		// When a controller type hints the dispatcher we'll make sure Laravel knows
		// to inject an instance of the registered API dispatcher.
		$this->app['Dingo\Api\Dispatcher'] = function($app)
		{
			return $app['dingo.api.dispatcher'];
		};

		$this->app['Dingo\Api\Authentication'] = function($app)
		{
			return $app['dingo.api.authentication'];
		};

		$this->app['router']->filter('api', function($route, $request)
		{
			try
			{
				$this->app['dingo.api.authentication']->authenticate();
			}
			catch (UnauthorizedHttpException $exception)
			{
				return new Response($exception->getMessage(), $exception->getStatusCode());
			}
		});
	}

	/**
	 * Register bindings for the service provider.
	 * 
	 * @return void
	 */
	public function register()
	{
		$this->registerDispatcher();

		$this->registerRouter();

		$this->registerExceptionHandler();

		$this->registerAuthentication();

		// We'll also register a booting event so that we can set our exception handler
		// instance, default API version and the API vendor on the router.
		$this->app->booting(function($app)
		{
			$app['router']->setExceptionHandler($app['dingo.api.exception']);

			$app['router']->setDefaultApiVersion($app['config']['api::version']);

			$app['router']->setApiVendor($app['config']['api::vendor']);
		});
	}

	/**
	 * Register and replace the bound router.
	 * 
	 * @return void
	 */
	protected function registerRouter()
	{
		$this->app['router'] = $this->app->share(function($app)
		{
			$router = new Router($app['events'], $app);

			if ($app['env'] == 'testing')
			{
				$router->disableFilters();
			}

			return $router;
		});
	}

	/**
	 * Register the API dispatcher.
	 * 
	 * @return void
	 */
	protected function registerDispatcher()
	{
		$this->app['dingo.api.dispatcher'] = $this->app->share(function($app)
		{
			return new Dispatcher($app['request'], $app['router'], $app['dingo.api.authentication']);
		});
	}

	/**
	 * Register the exception handler.
	 * 
	 * @return void
	 */
	protected function registerExceptionHandler()
	{
		$this->app['dingo.api.exception'] = $this->app->share(function($app)
		{
			return new ExceptionHandler;
		});
	}

	/**
	 * Register the API authentication.
	 * 
	 * @return void
	 */
	protected function registerAuthentication()
	{
		$this->app['dingo.api.authentication'] = $this->app->share(function($app)
		{
			$providers = [];

			$resolvers = [
				'basic'  => function($app) { return new BasicProvider($app['auth']); },
				'oauth2' => function($app) { return new OAuth2Provider($app['dingo.oauth.resource']); }
			];

			foreach ($app['config']['api::auth'] as $provider)
			{
				$providers[$provider] = $resolvers[$provider]($app);
			}

			return new Authentication($app['router'], $app['auth'], $providers);
		});
	}

}
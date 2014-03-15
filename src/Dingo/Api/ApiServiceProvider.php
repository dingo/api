<?php namespace Dingo\Api;

use Dingo\Api\Routing\Router;
use Dingo\Api\Routing\ApiRouter;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider {

	/**
	 * Boot the service provider.
	 * 
	 * @return void
	 */
	public function boot()
	{
		$this->package('dingo/api');

		// When a controller type hints the dispatcher we'll make sure Laravel knows
		// to inject an instance of the registered API dispatcher.
		$this->app['Dingo\Api\Dispatcher'] = function($app)
		{
			return $app['dingo.api.dispatcher'];
		};
	}

	/**
	 * Register bindings for the service provider.
	 * 
	 * @return void
	 */
	public function register()
	{
		$this->registerApi();

		$this->registerDispatcher();

		$this->registerRouter();

		$this->registerExceptionHandler();

		$this->registerAuthorization();

		// Register an API filter that enables the API routing when it is attached 
		// to a route, this will ensure that the response is correctly formatted
		// for any consumers.
		$this->app['router']->filter('api', function()
		{
			$this->app['router']->enableApiRouting();
		});

		// We'll also register a booting event so that we can set our API instance
		// on the router.
		$this->app->booting(function($app)
		{
			$app['router']->setApi($app['dingo.api']);
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
	 * Register the API.
	 * 
	 * @return void
	 */
	protected function registerApi()
	{
		$this->app['dingo.api'] = $this->app->share(function($app)
		{
			return new Api($app['request'], $app['dingo.api.exception'], $app['config']['api::vendor'], $app['config']['api::version']);
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
			return new Dispatcher($app['request'], $app['router'], $app['dingo.api']);
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
	 * Register the API authorization.
	 * 
	 * @return void
	 */
	protected function registerAuthorization()
	{
		$this->app['dingo.api.authorization'] = $this->app->share(function($app)
		{
			return new Authorization($app['dingo.oauth.server']);
		});
	}

}
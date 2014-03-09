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
			return $app['dingo.api'];
		};
	}

	/**
	 * Register bindings for the service provider.
	 * 
	 * @return void
	 */
	public function register()
	{
		$this->registerRouter();
	
		$this->registerDispatcher();

		// The API filter is applied automatically to routes that should be treated
		// as API requests.
		$this->app['router']->filter('api', function()
		{
			$this->app['router']->enableApi();
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
		$this->app['dingo.api'] = $this->app->share(function($app)
		{
			$dispatcher = new Dispatcher($app['request'], $app['router'], $app['config']['api::vendor']);

			$dispatcher->defaultsTo($app['config']['api::version']);

			return $dispatcher;
		});
	}

}
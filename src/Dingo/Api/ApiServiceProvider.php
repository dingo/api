<?php namespace Dingo\Api;

use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\AuthManager;
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
		$this->package('dingo/api');

		// When a controller type hints the dispatcher we'll make sure Laravel knows
		// to inject an instance of the registered API dispatcher.
		$this->app['Dingo\Api\Dispatcher'] = function($app)
		{
			return $app['dingo.api.dispatcher'];
		};

		$this->app['Dingo\Api\Authorization'] = function($app)
		{
			return $app['dingo.api.authorization'];
		};

		// Register an API filter that enables the API routing when it is attached 
		// to a route, this will ensure that the response is correctly formatted
		// for any consumers.
		$this->app['router']->filter('api', function($route, $request)
		{
			$this->app['router']->enableApiRouting();

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

		$this->registerAuthorization();

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
			return new Dispatcher($app['request'], $app['router']);
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
			$manager = new AuthManager($app);

			return new Authentication($app['router'], $manager, $app['config']['api::auth']);
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
			return new Authorization($app['dingo.oauth.authorization']);
		});
	}

}
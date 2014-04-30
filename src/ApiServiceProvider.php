<?php namespace Dingo\Api;

use Closure;
use Dingo\Api\Auth\Shield;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Auth\ProviderManager;
use League\Fractal\Manager as Fractal;
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

		$this->app['Dingo\Api\Dispatcher'] = function($app)
		{
			return $app['dingo.api.dispatcher'];
		};

		$this->app['Dingo\Api\Auth\Shield'] = function($app)
		{
			return $app['dingo.api.auth'];
		};

		$formats = [];

		foreach ($this->app['config']['api::formats'] as $key => $format)
		{
			if ($format instanceof Closure)
			{
				$format = call_user_func($format, $this->app);
			}

			$formats[$key] = $format;
		}

		Response::setFormatters($formats);
		Response::setTransformer($this->app['dingo.api.transformer']);
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

		$this->registerTransformer();

		$this->registerExceptionHandler();

		$this->registerAuthentication();

		$this->registerMiddlewares();

		// We'll also register a booting event so that we can set our exception handler
		// instance, default API version and the API vendor on the router.
		$this->app->booting(function($app)
		{
			$app['router']->setExceptionHandler($app['dingo.api.exception']);

			$app['router']->setDefaultVersion($app['config']['api::version']);

			$app['router']->setDefaultPrefix($app['config']['api::prefix']);

			$app['router']->setDefaultDomain($app['config']['api::domain']);

			$app['router']->setVendor($app['config']['api::vendor']);
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
			return new Dispatcher($app['request'], $app['url'], $app['router'], $app['dingo.api.auth']);
		});
	}

	/**
	 * Register the API transformer.
	 * 
	 * @return void
	 */
	protected function registerTransformer()
	{
		$this->app['dingo.api.transformer'] = $this->app->share(function($app)
		{
			return new Transformer(new Fractal, $app, $app['config']['api::embeds.key'], $app['config']['api::embeds.separator']);
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
		$this->app['dingo.api.auth'] = $this->app->share(function($app)
		{
			$providers = [];

			foreach ($app['config']['api::auth'] as $key => $provider)
			{
				if (is_callable($provider))
				{
					$provider = call_user_func($provider, $app);
				}

				$providers[$key] = $provider;
			}

			return new Shield($app['auth'], $providers);
		});
	}

	/**
	 * Register the middlewares.
	 * 
	 * @return void
	 */
	protected function registerMiddlewares()
	{
		$this->app->middleware('Dingo\Api\Http\Middleware\Authentication', [$this->app]);

		$this->app->middleware('Dingo\Api\Http\Middleware\RateLimit', [$this->app]);
	}

}

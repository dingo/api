<?php namespace Dingo\Api;

use Dingo\Api\Auth\SentryShield;
use RuntimeException;
use Dingo\Api\Auth\Shield;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Dingo\Api\Transformer\Factory;
use Dingo\Api\Auth\ProviderManager;
use League\Fractal\Manager as Fractal;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ApiServiceProvider extends ServiceProvider
{

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('dingo/api', 'api', __DIR__);

        $this->app['Dingo\Api\Dispatcher'] = function ($app) {
            return $app['dingo.api.dispatcher'];
        };
        $this->app['Dingo\Api\Auth\Shield'] = function ($app) {
            return $app['dingo.api.auth'];
        };

        $formats = $this->prepareResponseFormats();

        Response::setFormatters($formats);
        Response::setTransformer($this->app['dingo.api.transformer']);
    }

    /**
     * Prepare the response formats.
     *
     * @return array
     */
    protected function prepareResponseFormats()
    {
        $formats = [];

        foreach ($this->app['config']['api::formats'] as $key => $format) {
            if (is_callable($format)) {
                $format = call_user_func($format, $this->app);
            }

            $formats[$key] = $format;
        }

        if (empty($formats)) {
            throw new RuntimeException('No registered response formats.');
        }

        return $formats;
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
        $this->registerMiddlewares();

        $this->app->booting(function ($app) {
            // grab the setting for the auth driver your using.
            $shield_type = $app['config']->get('api::auth_provider');
            $shield_class = $app['config']->get("api::auth_drivers.$shield_type");

            $app['dingo.api.auth'] = $app->share(function ($app) use ($shield_type, $shield_class) {
                $providers = [];
                foreach ($app['config']['api::auth'] as $key => $provider) {
                    if (is_callable($provider)) {
                        $provider = call_user_func($provider, $app);
                    }
                    $providers[$key] = $provider;
                }
                return new $shield_class($app[$shield_type], $app, $providers);
            });

            $router = $app['router'];

            $router->setExceptionHandler($app['dingo.api.exception']);
            $router->setDefaultVersion($app['config']['api::version']);
            $router->setDefaultPrefix($app['config']['api::prefix']);
            $router->setDefaultDomain($app['config']['api::domain']);
            $router->setDefaultFormat($app['config']['api::default_format']);
            $router->setVendor($app['config']['api::vendor']);
        });
    }

    /**
     * Register and replace the bound router.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function ($app) {
            $router = new Router($app['events'], $app);

            if ($app['env'] == 'testing') {
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
        $this->app['dingo.api.dispatcher'] = $this->app->share(function ($app) {
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
        $this->app['dingo.api.transformer'] = $this->app->share(function ($app) {
            $factory = new Factory($app);

            if ($app['config']->has('api::transformer')) {
                $transformer = call_user_func($app['config']['api::transformer'], $app);

                $factory->setTransformer($transformer);
            }

            return $factory;
        });
    }

    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app['dingo.api.exception'] = $this->app->share(function ($app) {
            return new ExceptionHandler;
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

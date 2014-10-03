<?php

namespace Dingo\Api;

use RuntimeException;
use Dingo\Api\Auth\Shield;
use Dingo\Api\Routing\Router;
use Dingo\Api\Http\ResponseBuilder;
use League\Fractal\Manager as Fractal;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Console\ApiRoutesCommand;
use Illuminate\Support\Facades\Response;
use Dingo\Api\Http\Response as ApiResponse;
use Dingo\Api\Transformer\FractalTransformer;

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

        $this->bootContainerBindings();
        $this->bootResponseMacro();
        $this->bootResponseFormats();
        $this->bootResponseTransformer();
        $this->bootRouteAndAuthentication();
    }

    /**
     * Boot the container bindings.
     *
     * @return void
     */
    protected function bootContainerBindings()
    {
        $this->app['Dingo\Api\Dispatcher'] = function ($app) {
            return $app['dingo.api.dispatcher'];
        };

        $this->app['Dingo\Api\Auth\Shield'] = function ($app) {
            return $app['dingo.api.auth'];
        };

        $this->app['Dingo\Api\Http\ResponseBuilder'] = function ($app) {
            return $app['dingo.api.response'];
        };
    }

    /**
     * Boot the response facade macro.
     *
     * @return void
     */
    protected function bootResponseMacro()
    {
        Response::macro('api', function () {
            return $this->app['dingo.api.response'];
        });
    }

    /**
     * Boot the response transformer.
     *
     * @return void
     */
    protected function bootResponseTransformer()
    {
        ApiResponse::setTransformer($this->app['dingo.api.transformer']);
    }

    /**
     * Boot the response formats.
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function bootResponseFormats()
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

        ApiResponse::setFormatters($formats);
    }

    /**
     * Boot the current route and the authentication.
     *
     * @return void
     */
    protected function bootRouteAndAuthentication()
    {
        $this->app->booted(function ($app) {
            $request = $app['request'];
            $router = $app['router'];
            $collection = $router->getApiRouteCollectionFromRequest($request) ?: $router->getDefaultApiRouteCollection();

            // If the request is targetting the API we'll prepare the route by
            // revising it. This sets up the correct protection of the route
            // and any scopes that should be associated with it.
            if (! is_null($collection) && $router->requestTargettingApi($request)) {
                $route = (new Routing\ControllerReviser($app))->revise($collection->match($request));

                $app['dingo.api.auth']->setRoute($route);
                $app['dingo.api.auth']->setRequest($request);
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
        $this->registerResponseBuilder();
        $this->registerTransformer();
        $this->registerExceptionHandler();
        $this->registerAuthentication();
        $this->registerMiddlewares();
        $this->registerCommands();
        $this->registerBootingEvent();
    }

    /**
     * Register the booting event.
     *
     * @return void
     */
    protected function registerBootingEvent()
    {
        $this->app->booting(function ($app) {
            $router = $app['router'];

            $router->setExceptionHandler($app['dingo.api.exception']);
            $router->setDefaultVersion($app['config']['api::version']);
            $router->setDefaultPrefix($app['config']['api::prefix']);
            $router->setDefaultDomain($app['config']['api::domain']);
            $router->setDefaultFormat($app['config']['api::default_format']);
            $router->setVendor($app['config']['api::vendor']);
            $router->setConditionalRequest($app['config']['api::conditional_request']);
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
     * Register the API response builder.
     *
     * @return void
     */
    protected function registerResponseBuilder()
    {
        $this->app['dingo.api.response'] = $this->app->share(function ($app) {
            $transformer = $app['dingo.api.transformer'];

            if (! $transformer instanceof FractalTransformer) {
                $transformer = new FractalTransformer(new Fractal);
            }

            return new ResponseBuilder($transformer);
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
            $transformer = call_user_func($app['config']->get('api::transformer'), $app);

            $transformer->setContainer($app);

            return $transformer;
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
     * Register the API authentication.
     *
     * @return void
     */
    protected function registerAuthentication()
    {
        $this->app['dingo.api.auth'] = $this->app->share(function ($app) {
            $providers = [];

            foreach ($app['config']['api::auth'] as $key => $provider) {
                if (is_callable($provider)) {
                    $provider = call_user_func($provider, $app);
                }

                $providers[$key] = $provider;
            }

            return new Shield($app['auth'], $app, $providers);
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

    /**
     * Register the commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app['dingo.api.command.routes'] = $this->app->share(function ($app) {
            return new ApiRoutesCommand($app['router']);
        });

        $this->commands('dingo.api.command.routes');
    }
}

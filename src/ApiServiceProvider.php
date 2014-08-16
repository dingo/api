<?php

namespace Dingo\Api;

use RuntimeException;
use Dingo\Api\Auth\Shield;
use Dingo\Api\Routing\Router;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http\ResponseBuilder;
use League\Fractal\Manager as Fractal;
use Illuminate\Support\ServiceProvider;
use Dingo\Api\Console\ApiRoutesCommand;
use Dingo\Api\Routing\ControllerReviser;
use Illuminate\Support\Facades\Response;
use Dingo\Api\Http\Response as ApiResponse;
use Dingo\Api\Transformer\FractalTransformer;
use Dingo\Api\Events\RouterHandler;
use Dingo\Api\Events\AuthenticationHandler;
use Dingo\Api\Routing\ControllerDispatcher;

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
        $this->bootEvents();
        $this->bootResponseMacro();
        $this->bootResponseFormats();
        $this->bootResponseTransformer();
    }

    protected function bootEvents()
    {
        $events = $this->app['events'];

        $events->listen('router.exception', 'Dingo\Api\Events\RouterHandler@handleException');
        $events->listen('router.matched', 'Dingo\Api\Events\RouterHandler@handleControllerRevising');
        $events->listen('router.filter: api.auth', 'Dingo\Api\Events\AuthenticationHandler@handleRequestAuthentication');
    }

    /**
     * Boot the container bindings.
     * 
     * @return void
     */
    protected function bootContainerBindings()
    {
        $this->app->bind('Dingo\Api\Dispatcher', function ($app) {
            return $app['api.dispatcher'];
        });

        $this->app->bind('Dingo\Api\Auth\Shield', function ($app) {
            return $app['api.auth'];
        });

        $this->app->bind('Dingo\Api\Http\ResponseBuilder', function ($app) {
            return $app['api.response'];
        });

        $this->app->bind('Dingo\Api\Events\RouterHandler', function ($app) {
            return new RouterHandler($app['router'], new Handler, new ControllerReviser($app));
        });

        $this->app->bind'Dingo\Api\Events\AuthenticationHandler', function ($app) {
            return new AuthenticationHandler($app['router'], $app['events'], $app['api.auth']);
        });
    }

    /**
     * Boot the response facade macro.
     * 
     * @return void
     */
    protected function bootResponseMacro()
    {
        Response::macro('api', function () {
            return $this->app['api.response'];
        });
    }

    /**
     * Boot the response transformer.
     * 
     * @return void
     */
    protected function bootResponseTransformer()
    {
        ApiResponse::setTransformer($this->app['api.transformer']);
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
            $app['router']->setDefaultVersion($app['config']->get('api::version'));
            $app['router']->setDefaultPrefix($app['config']->get('api::prefix'));
            $app['router']->setDefaultDomain($app['config']->get('api::domain'));
            $app['router']->setDefaultFormat($app['config']->get('api::default_format'));
            $app['router']->setVendor($app['config']->get('api::vendor'));
            $app['router']->setConditionalRequest($app['config']->get('api::conditional_request'));
        });
    }

    /**
     * Register and replace the bound router.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->bindShared('router', function ($app) {
            $router = new Router($app['events'], $app);
            $dispatcher = new ControllerDispatcher($router, $app['api.dispatcher'], $app['api.auth'], $app['api.response'], $app);
            
            $router->setControllerDispatcher($dispatcher);

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
        $this->app->bindShared('api.response', function ($app) {
            $transformer = $app['api.transformer'];

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
        $this->app->bindShared('api.dispatcher', function ($app) {
            return new Dispatcher($app['request'], $app['url'], $app['router'], $app['api.auth']);
        });
    }

    /**
     * Register the API transformer.
     *
     * @return void
     */
    protected function registerTransformer()
    {
        $this->app->bindShared('api.transformer', function ($app) {
            $transformer = call_user_func($app['config']->get('api::transformer'), $app);

            $transformer->setContainer($app);

            return $transformer;
        });
    }

    /**
     * Register the API authentication.
     *
     * @return void
     */
    protected function registerAuthentication()
    {
        $this->app->bindShared('api.auth', function ($app) {
            $providers = [];

            foreach ($app['config']['api::auth'] as $key => $provider) {
                if (is_callable($provider)) {
                    $provider = call_user_func($provider, $app);
                }

                $providers[$key] = $provider;
            }

            return new Shield($app['router'], $app, $providers);
        });
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bindShared('api.command.routes', function ($app) {
            return new ApiRoutesCommand($app['router']);
        });

        $this->commands('api.command.routes');
    }
}

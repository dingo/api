<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Exception;
use Dingo\Api\Routing\Router;
use Dingo\Api\Exception\Handler;
use Illuminate\Pipeline\Pipeline;
use Dingo\Api\Http\RequestValidator;
use Dingo\Api\Http\Request as HttpRequest;
use Illuminate\Contracts\Foundation\Application;

class Request
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Exception handler instance.
     *
     * @var \Dingo\Api\Exception\Handler
     */
    protected $exception;

    /**
     * Router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * HTTP validator instance.
     *
     * @var \Dingo\Api\Http\Validator
     */
    protected $validator;

    /**
     * Array of middleware.
     *
     * @var array
     */
    protected $middleware;

    /**
     * Create a new request middleware instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Dingo\Api\Exception\Handler                 $exception
     * @param \Dingo\Api\Routing\Router                    $router
     * @param \Dingo\Api\Http\RequestValidator             $validator
     * @param array                                        $middleware
     *
     * @return void
     */
    public function __construct(Application $app, Handler $exception, Router $router, RequestValidator $validator, array $middleware)
    {
        $this->app = $app;
        $this->exception = $exception;
        $this->router = $router;
        $this->validator = $validator;
        $this->middleware = $middleware;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if ($this->validator->validateRequest($request)) {
                $this->app->singleton('Illuminate\Contracts\Debug\ExceptionHandler', function ($app) {
                    return $app['Dingo\Api\Exception\Handler'];
                });

                $request = $this->app->make('Dingo\Api\Contract\Http\Request')->createFromIlluminate($request);

                return $this->sendRequestThroughRouter($request);
            }
        } catch (Exception $exception) {
            return $this->exception->handle($exception);
        }

        return $next($request);
    }

    /**
     * Send the request through the Dingo router.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    protected function sendRequestThroughRouter(HttpRequest $request)
    {
        $this->app->instance('request', $request);

        return (new Pipeline($this->app))->send($request)->through($this->middleware)->then(function ($request) {
            return $this->router->dispatch($request);
        });
    }
}

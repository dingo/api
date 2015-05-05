<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use ReflectionClass;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Validator;
use Dingo\Api\Routing\Router;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Contracts\Foundation\Application;

class RequestMiddleware
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

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
     * Create a new request middleware instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Dingo\Api\Routing\Router                    $router
     * @param \Dingo\Api\Http\Validator                    $validator
     *
     * @return void
     */
    public function __construct(Application $app, Router $router, Validator $validator)
    {
        $this->app = $app;
        $this->router = $router;
        $this->validator = $validator;
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
        if ($this->validator->validateRequest($request)) {
            $request = Request::createFromExisting($request);

            return $this->sendRequestThroughRouter($request);
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
    protected function sendRequestThroughRouter(Request $request)
    {
        $this->app->instance('request', $request);

        return (new Pipeline($this->app))->send($request)->through($this->gatherAppMiddleware())->then(function ($request) {
            return $this->router->dispatch($request);
        });
    }

    /**
     * Gather the application middleware besides this one so that we can send
     * our request through them, exactly how the developer wanted.
     *
     * @return array
     */
    protected function gatherAppMiddleware()
    {
        $reflection = new ReflectionClass($this->app);

        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);

        $middleware = $property->getValue($this->app);

        array_forget($middleware, array_search('Dingo\Api\Http\Middleware\RequestMiddleware', $middleware));

        $property->setAccessible(false);

        return $middleware;
    }
}
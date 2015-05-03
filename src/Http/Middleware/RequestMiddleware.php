<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use ReflectionClass;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Validator;
use Dingo\Api\Routing\Router;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class RequestMiddleware
{
    /**
     * HTTP Validator instance.
     *
     * @var \Dingo\Api\Http\Validator
     */
    protected $validator;

    /**
     * Create a new request middleware instance.
     *
     * @param \Dingo\Api\Http\Validator $validator
     *
     * @return void
     */
    public function __construct(ApplicationContract $app, Router $router, Validator $validator)
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

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        return (new Pipeline($this->app))->send($request)->through($this->gatherAppMiddleware())->then(function ($request) {
            return $this->router->dispatch($request);
        });
    }

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

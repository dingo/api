<?php

namespace Dingo\Api\Http\Middleware;

use Dingo\Api\Http\Response;
use Illuminate\Routing\Route;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Container\Container;
use Dingo\Api\Routing\ControllerReviser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authentication implements HttpKernelInterface
{
    /**
     * The wrapped kernel implementation.
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $app;

    /**
     * Laravel application container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Controller reviser instance.
     *
     * @var \Dingo\Api\Routing\ControllerReviser
     */
    protected $controllerReviser;

    /**
     * Array of resolved container bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Array of binding mappings.
     *
     * @var array
     */
    protected $mappings = ['auth' => 'dingo.api.auth'];

    /**
     * Create a new authentication middleware instance.
     *
     * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
     * @param  \Illuminate\Container\Container  $container
     * @param  \Dingo\Api\Routing\ControllerReviser  $controllerReviser
     * @return void
     */
    public function __construct(HttpKernelInterface $app, Container $container, ControllerReviser $controllerReviser = null)
    {
        $this->app = $app;
        $this->container = $container;
        $this->controllerReviser = $controllerReviser ?: new ControllerReviser($container);
    }

    /**
     * Handle a given request and return the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  int  $type
     * @param  bool  $catch
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // Our middleware needs to ensure that Laravel is booted before we
        // can do anything. This gives us access to all the booted
        // service providers and other container bindings.
        $this->container->boot();

        if ($request instanceof InternalRequest || $this->auth->user()) {
            return $this->app->handle($request, $type, $catch);
        }

        // If a collection exists for the request and we can match a route
        // from the request then we'll check to see if the route is
        // protected and, if it is, we'll attempt to authenticate.
        if ($this->router->requestTargettingApi($request) && $collection = $this->getApiRouteCollection($request)) {
            try {
                $route = $this->controllerReviser->revise($collection->match($request));

                if ($this->routeIsProtected($route)) {
                    return $this->authenticate($request, $route) ?: $this->app->handle($request, $type, $catch);
                }
            } catch (HttpExceptionInterface $exception) {
                // If we catch an HTTP exception then we'll simply let
                // the wrapping kernel do its thing.
            }
        }

        return $this->app->handle($request, $type, $catch);
    }

    /**
     * Get the API route collection for the given request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Dingo\Api\Routing\ApiRouteCollection
     */
    protected function getApiRouteCollection(Request $request)
    {
        return $this->router->getApiRouteCollectionFromRequest($request) ?: $this->router->getDefaultApiRouteCollection();
    }

    /**
     * Authenticate the request for the given route.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Illuminate\Routing\Route  $route
     * @return null|\Dingo\Api\Http\Response
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function authenticate(Request $request, Route $route)
    {
        try {
            $this->auth->authenticate($request, $route);
        } catch (UnauthorizedHttpException $exception) {
            $response = $this->router->handleException($exception);

            list ($version, $format) = $this->router->parseAcceptHeader($request);

            return Response::makeFromExisting($response)->morph($format);
        }
    }

    /**
     * Determine if a route is protected.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return bool
     */
    protected function routeIsProtected(Route $route)
    {
        $action = $route->getAction();

        return in_array('protected', $action, true) || array_get($action, 'protected') === true;
    }

    /**
     * Dynamically handle binding calls on the container.
     *
     * @param  string  $binding
     * @return mixed
     */
    public function __get($binding)
    {
        $binding = array_get($this->mappings, $binding, $binding);

        if (isset($this->bindings[$binding])) {
            return $this->bindings[$binding];
        }

        return $this->bindings[$binding] = $this->container->make($binding);
    }
}

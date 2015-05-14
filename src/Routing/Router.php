<?php

namespace Dingo\Api\Routing;

use Closure;
use Exception;
use RuntimeException;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Http\InternalRequest;
use Dingo\Api\Http\ResponseBuilder;
use Illuminate\Container\Container;
use Dingo\Api\Routing\Adapter\Adapter;
use Dingo\Api\Exception\InternalHttpException;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class Router
{
    const API_AUTH_MIDDLEWARE = 'api.auth';

    /**
     * Routing adapter instance.
     *
     * @var \Dingo\Api\Routing\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * Accept parser instance.
     *
     * @var \Dingo\Api\Http\Parser\AcceptParser
     */
    protected $accept;

    /**
     * Exception handler instance.
     *
     * @var \Dingo\Api\Exception\Handler
     */
    protected $exception;

    /**
     * Application container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Group stack array.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * Indicates if the request is conditional.
     *
     * @var bool
     */
    protected $conditionalRequest;

    /**
     * The current route being dispatched.
     *
     * @var \Dingo\Api\Routing\Route
     */
    protected $currentRoute;

    /**
     * Array of protected routes.
     *
     * @var array
     */
    protected $protectedRoutes = [];

    /**
     * Create a new router instance.
     *
     * @param \Dingo\Api\Routing\Adapter\Adapter  $adapter
     * @param \Dingo\Api\Http\Parser\AcceptParser $accept
     * @param \Dingo\Api\Exception\Handler        $exception
     * @param \Illuminate\Container\Container     $container
     *
     * @return void
     */
    public function __construct(Adapter $adapter, AcceptParser $accept, Handler $exception, Container $container)
    {
        $this->adapter = $adapter;
        $this->accept = $accept;
        $this->exception = $exception;
        $this->container = $container;
    }

    /**
     * An alias for calling the group method, allows a more fluent API
     * for registering a new API version group with optional
     * attributes and a required callback.
     *
     * This method can be called without the third parameter, however,
     * the callback should always be the last paramter.
     *
     * @param string         $version
     * @param array|callable $second
     * @param callable       $third
     *
     * @return void
     */
    public function version($version, $second, $third = null)
    {
        if (func_num_args() == 2) {
            list($version, $callback, $attributes) = array_merge(func_get_args(), [[]]);
        } else {
            list($version, $attributes, $callback) = func_get_args();
        }

        $attributes = array_merge($attributes, ['version' => $version]);

        $this->group($attributes, $callback);
    }

    /**
     * Create a new route group.
     *
     * @param array    $attributes
     * @param callable $callback
     *
     * @return void
     */
    public function group(array $attributes, $callback)
    {
        $attributes = $this->mergeLastGroupAttributes($attributes);

        if (! isset($attributes['version'])) {
            throw new RuntimeException('A version is required for an API group definition.');
        } else {
            $attributes['version'] = (array) $attributes['version'];
        }

        $this->groupStack[] = $attributes;

        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * Create a new GET route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function get($uri, $action)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Create a new POST route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Create a new PUT route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Create a new PATCH route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Create a new DELETE route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Create a new OPTIONS route.
     *
     * @param string                $uri
     * @param array|string|callable $action
     *
     * @return mixed
     */
    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Add a route to the routing adapter.
     *
     * @param string|array          $methods
     * @param string                $uri
     * @param string|array|callable $action
     *
     * @return mixed
     */
    public function addRoute($methods, $uri, $action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        } elseif ($action instanceof Closure) {
            $action = [$action];
        }

        $action = $this->mergeLastGroupAttributes($action);

        $uri = $uri === '/' ? $uri : '/'.trim($uri, '/');

        if (isset($action['prefix'])) {
            $uri = trim($action['prefix'], '/').'/'.trim($uri, '/');

            unset($action['prefix']);
        }

        $action = $this->addRouteMiddlewares($action);

        return $this->adapter->addRoute((array) $methods, $action['version'], $uri, $action);
    }

    protected function addRouteMiddlewares(array $action)
    {
        foreach ([static::API_AUTH_MIDDLEWARE] as $middleware) {
            if (($key = array_search($middleware, $action['middleware'])) !== false) {
                unset($action['middleware'][$key]);
            }

            array_unshift($action['middleware'], $middleware);
        }

        return $action;
    }

    /**
     * Merge the last groups attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function mergeLastGroupAttributes(array $attributes)
    {
        if (empty($this->groupStack)) {
            return $this->mergeGroup($attributes, []);
        }

        return $this->mergeGroup($attributes, end($this->groupStack));
    }

    /**
     * Merge the given group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function mergeGroup(array $new, array $old)
    {
        $new['namespace'] = $this->formatNamespace($new, $old);

        $new['prefix'] = $this->formatPrefix($new, $old);

        $new['before'] = $this->formatBefore($new, $old);

        $new['after'] = $this->formatAfter($new, $old);

        $new['middleware'] = $this->formatMiddleware($new, $old);

        $new['scopes'] = $this->formatScopes($new, $old);

        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        if (isset($new['version'])) {
            unset($old['version']);
        }

        if (isset($new['uses'])) {
            $new['uses'] = $this->formatUses($new);
        }

        $new['where'] = array_merge(array_get($old, 'where', []), array_get($new, 'where', []));

        return array_merge_recursive(array_except($old, ['namespace', 'prefix', 'where', 'scopes', 'before', 'after']), $new);
    }

    /**
     * Format the middleware in a route action.
     *
     * @param array $new
     *
     * @return array
     */
    protected function formatMiddleware(array $new)
    {
        $middleware = array_get($new, 'middleware', []);

        return is_string($middleware) ? explode('|', $middleware) : $middleware;
    }

    /**
     * Format the before filters in a route action.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function formatBefore(array $new, array $old)
    {
        return $this->formatBeforeOrAfter('before', $new, $old);
    }

    /**
     * Format the after filters in a route action.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function formatAfter(array $new, array $old)
    {
        return $this->formatBeforeOrAfter('after', $new, $old);
    }

    /**
     * Format the before or after filters in a route action.
     *
     * @param string $filter
     * @param array  $new
     * @param array  $old
     *
     * @return array
     */
    protected function formatBeforeOrAfter($filter, array $new, array $old)
    {
        $newFilters = array_get($new, $filter, []);

        if (is_string($newFilters)) {
            $newFilters = explode('|', $newFilters);
        }

        $oldFilters = array_get($old, $filter, []);

        if (is_string($oldFilters)) {
            $oldFilters = explode('|', $oldFilters);
        }

        return array_merge($oldFilters, $newFilters);
    }

    /**
     * Format the uses key in a route action.
     *
     * @param array $new
     *
     * @return string
     */
    protected function formatUses(array $new)
    {
        if (isset($new['namespace']) && is_string($new['uses']) && strpos($new['uses'], '\\') === false) {
            return $new['namespace'].'\\'.$new['uses'];
        }

        return $new['uses'];
    }

    /**
     * Format the scopes in a route action.
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function formatScopes(array $new, array $old)
    {
        $scopes = [];

        if (isset($new['scopes']) && isset($old['scopes'])) {
            $scopes = array_merge((array) $old['scopes'], (array) $new['scopes']);
        } elseif (isset($new['scopes'])) {
            $scopes = (array) $new['scopes'];
        } elseif (isset($old['scopes'])) {
            $scopes = (array) $old['scopes'];
        }

        foreach ($scopes as $key => $scope) {
            if (! is_array($scope) && str_contains($scope, '|')) {
                unset($scopes[$key]);

                $scopes = array_merge($scopes, explode('|', $scope));
            }
        }

        return $scopes;
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    protected function formatNamespace(array $new, array $old)
    {
        if (isset($new['namespace']) && isset($old['namespace'])) {
            return trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\');
        } elseif (isset($new['namespace'])) {
            return trim($new['namespace'], '\\');
        }

        return array_get($old, 'namespace');
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string
     */
    protected function formatPrefix($new, $old)
    {
        if (isset($new['prefix'])) {
            return trim(array_get($old, 'prefix'), '/').'/'.trim($new['prefix'], '/');
        }

        return array_get($old, 'prefix');
    }

    /**
     * Dispatch a request via the adapter.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function dispatch(Request $request)
    {
        $accept = $this->accept->parse($request);

        $this->container->instance('Dingo\Api\Http\Request', $request);

        try {
            $response = $this->adapter->dispatch($request, $accept['version']);

            if (! $response->isSuccessful()) {
                throw new HttpException($response->getStatusCode(), $response->getContent());
            }

            return $this->prepareResponse($response, $request, $accept['format']);
        } catch (Exception $exception) {
            return $this->prepareResponse(
                $this->exception->handle($exception),
                $request,
                $accept['format']
            );
        }
    }

    /**
     * Prepare a response by transforming and formatting it correctly.
     *
     * @param \Illuminate\Http\Response $response
     * @param \Dingo\Api\Http\Request   $request
     * @param string                    $format
     *
     * @return \Dingo\Api\Http\Response
     */
    protected function prepareResponse(IlluminateResponse $response, Request $request, $format)
    {
        if ($response instanceof ResponseBuilder) {
            $response = $response->build();
        } elseif (! $response instanceof Response) {
            $response = Response::makeFromExisting($response);
        }

        // If we try and get a formatter that does not exist we'll let the exception
        // handler deal with it. At worst we'll get a generic JSON response that
        // a consumer can hopefully deal with. Ideally they won't be using
        // an unsupported format.
        try {
            $response->getFormatter($format)->setResponse($response)->setRequest($request);
        } catch (NotAcceptableHttpException $exception) {
            return $this->exception->handle($exception);
        }

        $response = $response->morph($format);

        if ($response->isSuccessful() && $this->requestIsConditional()) {
            if (! $response->headers->has('ETag')) {
                $response->setEtag(md5($response->getContent()));
            }

            $response->isNotModified($request);
        }

        return $response;
    }

    /**
     * Determine if the request is conditional.
     *
     * @return bool
     */
    protected function requestIsConditional()
    {
        return $this->conditionalRequest;
    }

    /**
     * Set the conditional request.
     *
     * @param bool $conditionalRequest
     *
     * @return void
     */
    public function setConditionalRequest($conditionalRequest)
    {
        $this->conditionalRequest = $conditionalRequest;
    }

    /**
     * Get the current request instance.
     *
     * @return \Dingo\Api\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->container['request'];
    }

    /**
     * Get the current route instance.
     *
     * @return \Dingo\Api\Routing\Route
     */
    public function getCurrentRoute()
    {
        if (isset($this->currentRoute)) {
            return $this->currentRoute;
        }

        $request = $this->container['request'];

        return $this->currentRoute = new Route($request->route(), $request);
    }
}

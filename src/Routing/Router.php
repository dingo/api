<?php

namespace Dingo\Api\Routing;

use Closure;
use RuntimeException;
use Dingo\Api\Http\Request;
use Illuminate\Container\Container;
use Dingo\Api\Http\Parser\AcceptParser;
use Dingo\Api\Routing\Adapter\AdapterInterface;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Http\Response as IlluminateResponse;

class Router
{
    protected $versions = [];

    protected $adapter;

    protected $accept;

    protected $container;

    protected $groupStack = [];

    public function __construct(AdapterInterface $adapter, AcceptParser $accept, Container $container)
    {
        $this->adapter = $adapter;
        $this->accept = $accept;
        $this->container = $container;
    }

    public function version($version, $callback)
    {
        $this->group(['version' => $version], $callback);
    }

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

    public function get($uri, $action)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function options($uri, $action)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    public function addRoute($methods, $uri, $action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        } elseif ($action instanceof Closure) {
            $action = [$action];
        }

        $action = $this->mergeLastGroupAttributes($action);

        $uri = $uri === '/' ? $uri : '/'.trim($uri, '/');

        // To trick the container router into thinking the route exists we'll
        // need to register a dummy action with the router. This ensures
        // that the router processes the middleware and allows the API
        // router to be booted and used as the dispatcher.
        $this->registerRouteWithContainerRouter($methods, $uri, null);

        return $this->adapter->addRoute((array) $methods, $action['version'], $uri, $action);
    }

    protected function mergeLastGroupAttributes(array $attributes)
    {
        if (empty($this->groupStack)) {
            return $attributes;
        }

        return $this->mergeGroup($attributes, end($this->groupStack));
    }

    /**
     * Merge the given group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected function mergeGroup(array $new, array $old)
    {
        $new['namespace'] = $this->formatNamespace($new, $old);

        $new['prefix'] = $this->formatPrefix($new, $old);

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

        return array_merge_recursive(array_except($old, array('namespace', 'prefix', 'where')), $new);
    }

    protected function formatUses($new)
    {
        if (isset($new['namespace']) && is_string($new['uses']) && strpos($new['uses'], '\\') === false) {
            return $new['namespace'].'\\'.$new['uses'];
        }

        return $new['uses'];
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * @param  array  $new
     * @param  array  $old
     * @return string
     */
    protected function formatNamespace($new, $old)
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

    protected function registerRouteWithContainerRouter($methods, $uri, $action)
    {
        $router = ($this->container instanceof LumenApplication) ? $this->container : $this->container['router'];

        foreach ((array) $methods as $method) {
            if ($method != 'HEAD') {
                $this->container->{$method}($uri, $action);
            }
        }
    }

    public function dispatch(Request $request)
    {
        $accept = $this->accept->parse($request);

        return $this->prepareResponse(
            $this->adapter->dispatch($request, $accept['version'])
        );
    }

    protected function prepareResponse(IlluminateResponse $response)
    {

    }
}

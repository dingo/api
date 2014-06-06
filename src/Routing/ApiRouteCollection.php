<?php

namespace Dingo\Api\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;

class ApiRouteCollection extends RouteCollection
{
    /**
     * Version of this collection of routes.
     *
     * @var string
     */
    protected $version;

    /**
     * Options specified on this collection of routes.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new API route collection instance.
     *
     * @param  string  $version
     * @param  array  $options
     * @return void
     */
    public function __construct($version, array $options)
    {
        $this->version = $version;
        $this->options = $options;
    }

    /**
     * Get an option from the collection.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function option($key, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    /**
     * Determine if the routes within the collection will be a match for
     * the current request. If not prefix or domain is set on the
     * collection then it's assumed it will be a match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matchesRequest(Request $request)
    {
        if ($this->matchesCollectionVersion($request)) {
            if ($this->matchDomain($request)) {
                return true;
            } elseif ($this->matchPrefix($request)) {
                return true;
            } elseif (! $this->option('prefix') and ! $this->option('domain')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the requested version matches the collection version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function matchesCollectionVersion($request)
    {
        if (preg_match('#application/vnd\.\w+.(v[\d\.]+)\+\w+#', $request->header('accept'), $matches)) {
            list ($accept, $version) = $matches;

            return $version == $this->version;
        }

        return false;
    }

    /**
     * Matches domain if is set on route group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function matchDomain($request)
    {
        return $this->option('domain') and $request->header('host') == $this->option('domain');
    }

    /**
     * Matches prefix if is set in route group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function matchPrefix($request)
    {
        if (! $prefix = $this->option('prefix')) {
            return false;
        }

        $prefix = $this->filterAndExplode($this->option('prefix'));

        $path = $this->filterAndExplode($request->getPathInfo());

        return $prefix == array_slice($path, 0, count($prefix));
    }

    /**
     * Explode array on slash and remove empty values.
     *
     * @param  array  $array
     * @return array
     */
    protected function filterAndExplode($array)
    {
        return array_filter(explode('/', $array));
    }
}

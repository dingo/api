<?php

namespace Dingo\Api\Routing;

use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Illuminate\Routing\RouteCollection as IlluminateRouteCollection;

class RouteCollection extends IlluminateRouteCollection
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
     * @param string $version
     * @param array  $options
     *
     * @return void
     */
    public function __construct($version, array $options = [])
    {
        $this->version = $version;
        $this->options = $options;
    }

    /**
     * Get an option from the collection.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function option($key, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    /**
     * Determine if the collection will match on the vesrion.
     *
     * @param array|string $versions
     *
     * @return bool
     */
    public function matchesVersion($versions)
    {
        foreach ((array) $versions as $version) {
            if ($this->version == $version) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the collection will match on the domain.
     *
     * @param string $domain
     *
     * @return bool
     */
    public function matchesDomain($domain)
    {
        return $this->option('domain') && $this->option('domain') == $domain;
    }

    /**
     * Determine if the routes within the collection will be a match for
     * the current request. If no prefix or domain is set on the
     * collection then it's assumed it will be a match.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function matchesRequest(Request $request)
    {
        if ($this->headerVersionMatches($request)) {
            if ($this->matchDomain($request)) {
                return true;
            } elseif ($this->matchPrefix($request)) {
                return true;
            } elseif (! $this->option('prefix') && ! $this->option('domain')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the header version matches the collection version.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function headerVersionMatches($request)
    {
        if (preg_match('#application/vnd\.\w+.(v[\d\.]+)\+\w+#', $request->header('accept'), $matches)) {
            list($accept, $version) = $matches;

            return $version == $this->version;
        }

        return false;
    }

    /**
     * Matches domain if is set on route group.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function matchDomain($request)
    {
        return $this->option('domain') && $request->header('host') == $this->option('domain');
    }

    /**
     * Matches prefix if is set in route group.
     *
     * @param \Illuminate\Http\Request $request
     *
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
     * @param array $array
     *
     * @return array
     */
    protected function filterAndExplode($array)
    {
        return array_filter(explode('/', $array));
    }

    /**
     * {@inheritDoc}
     */
    protected function getOtherMethodsRoute($request, array $others)
    {
        if ($request->method() == 'OPTIONS') {
            return (new Route('OPTIONS', $request->path(), function () use ($others) {
                return new Response('', 200, ['Allow' => implode(',', $others)]);
            }))->bind($request);
        } else {
            $this->methodNotAllowed($others);
        }
    }
}

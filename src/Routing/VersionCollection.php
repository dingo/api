<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Config;
use Illuminate\Http\Request;

class VersionCollection
{
    /**
     * API config instance.
     *
     * @var \Dingo\Api\Config
     */
    protected $config;

    /**
     * Array of API route collections.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Create a new version collection instance.
     *
     * @param  \Dingo\Api\Config  $config
     * @return void
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Add a version to the collection.
     *
     * @param  string  $version
     * @param  array  $options
     * @return \Dingo\Api\Routing\RouteCollection
     */
    public function add($version, array $options)
    {
        return $this->routes[$version] = new RouteCollection($version, $options);
    }

    /**
     * Determine if the version exists on the collection.
     *
     * @param  string  $version
     * @return bool
     */
    public function has($version)
    {
        return isset($this->routes[$version]);
    }

    /**
     * Get a matching API route collection from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getByRequest(Request $request)
    {
        return array_first($this->routes, function ($key, $collection) use ($request) {
            return $collection->matchesRequest($request);
        });
    }

    /**
     * Get the default API route collection.
     *
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function getDefault()
    {
        return $this->get($this->config->getVersion());
    }

    /**
     * Get an API route collection for a given version.
     *
     * @param  string  $version
     * @return \Dingo\Api\Routing\RouteCollection|null
     */
    public function get($version)
    {
        return array_get($this->routes, $version);
    }

    /**
     * Get all routes.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Determine if the version collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->routes);
    }
}

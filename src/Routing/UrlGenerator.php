<?php

namespace Dingo\Api\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator as IlluminateUrlGenerator;

class UrlGenerator extends IlluminateUrlGenerator
{
    protected $collections;

    /**
     * Create a new URL generator instance.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    /**
     * Set the routes to use from the version.
     *
     * @param string $version
     *
     * @return \Dingo\Api\Routing\UrlGenerator
     */
    public function version($version)
    {
        $this->routes = $this->collections[$version];

        return $this;
    }

    /**
     * Set the route collection instance.
     *
     * @param \Dingo\Api\Routing\RouteCollection $routes
     *
     * @return void
     */
    public function setRouteCollections(array $collections)
    {
        $this->collections = $collections;
    }
}

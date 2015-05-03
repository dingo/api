<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\RouteCollection;

class VersionCollection
{
    protected $versions = [];

    public function add(Route $route)
    {
        $this->addRouteToRouteCollectionVersions($route);

        return $route;
    }

    protected function addRouteToRouteCollectionVersions(Route $route)
    {
        $versions = $route->getVersions();

        foreach ($versions as $version) {
            if (! isset($this->versions[$version])) {
                $this->versions[$version] = new RouteCollection;
            }

            $this->versions[$version]->add($route);
        }
    }
}

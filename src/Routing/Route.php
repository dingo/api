<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\Route as IlluminateRoute;

class Route extends IlluminateRoute
{
    protected function parseAction($action)
    {
        $action = parent::parseAction($action);

        return $action;
    }

    public function getVersions()
    {
        return (array) $this->action['version'];
    }
}

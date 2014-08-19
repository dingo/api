<?php

namespace Dingo\Api\Routing;

use ReflectionMethod;
use Illuminate\Routing\ControllerInspector as IlluminateControllerInspector;

class ControllerInspector extends IlluminateControllerInspector
{
    /**
     * Determine if the given controller method is routable.
     *
     * @param  ReflectionMethod  $method
     * @param  string  $controller
     * @return bool
     */
    public function isRoutable(ReflectionMethod $method, $controller = null)
    {
        if ($method->class == 'Dingo\Api\Routing\Controller') {
            return false;
        }

        return parent::isRoutable($method, $controller);
    }
}

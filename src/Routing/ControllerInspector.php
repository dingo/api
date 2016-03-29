<?php

namespace Dingo\Api\Routing;

use ReflectionMethod;
use Illuminate\Routing\ControllerInspector as IlluminateControllerInspector;

class ControllerInspector extends IlluminateControllerInspector
{
    /**
     * Methods on the Helpers trait that are not routable.
     *
     * @var array
     */
    protected $unroutable = ['getThrottles', 'getRateLimit', 'getScopes', 'getAuthenticationProviders'];

    /**
     * {@inheritDoc}
     */
    public function isRoutable(ReflectionMethod $method)
    {
        if (in_array($method->name, $this->unroutable)) {
            return false;
        }

        return parent::isRoutable($method);
    }
}

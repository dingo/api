<?php

namespace Dingo\Api\Routing;

use Illuminate\Routing\Route as IlluminateRoute;

class Route extends IlluminateRoute
{
    /**
     * {@inheritDoc}
     */
    protected function parseAction($action)
    {
        $action = parent::parseAction($action);

        if (isset($action['protected'])) {
            $action['protected'] = is_array($action['protected']) ? last($action['protected']) : $action['protected'];
        }

        return $action;
    }

    /**
     * Determine if the route is protected.
     * 
     * @return bool
     */
    public function isProtected()
    {
        return in_array('protected', $this->action, true) || (isset($this->action['protected']) && $this->action['protected'] === true);
    }
}

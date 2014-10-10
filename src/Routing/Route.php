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

        if (isset($action['scopes'])) {
            $action['scopes'] = is_array($action['scopes']) ? $action['scopes'] : explode('|', $action['scopes']);
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
        return in_array('protected', $this->action, true) || array_get($this->action, 'protected') === true;
    }

    /**
     * Get the routes scopes.
     *
     * @return array
     */
    public function scopes()
    {
        return array_get($this->action, 'scopes', []);
    }
}

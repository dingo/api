<?php

namespace Dingo\Api\Routing;

use ErrorException;

trait Helpers
{
    /**
     * Get the authenticated user.
     *
     * @return mixed
     */
    protected function user()
    {
        return app('Dingo\Api\Auth\Auth')->user();
    }

    /**
     * Get the auth instance.
     *
     * @return \Dingo\Api\Auth\Auth
     */
    protected function auth()
    {
        return app('Dingo\Api\Auth\Auth');
    }

    /**
     * Magically handle calls to certain properties.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, ['user', 'auth']) && method_exists($this, $key)) {
            return $this->$key();
        }

        throw new ErrorException('Undefined property '.get_class($this).'::'.$key);
    }
}

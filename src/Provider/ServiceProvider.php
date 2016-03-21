<?php

namespace Dingo\Api\Provider;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

abstract class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Retrieve and instantiate a config value if it exists and is a class.
     *
     * @param string $key
     * @param bool   $instantiate
     *
     * @return mixed
     */
    protected function config($key, $instantiate = true)
    {
        $value = $this->app['config']->get('api.'.$key);

        if (is_array($value)) {
            return $instantiate ? $this->instantiateConfigValues($value) : $value;
        }

        return $instantiate ? $this->instantiateConfigValue($value) : $value;
    }

    /**
     * Instantiate an array of instantiable configuration values.
     *
     * @param array $values
     *
     * @return array
     */
    protected function instantiateConfigValues(array $values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->instantiateConfigValue($value);
        }

        return $values;
    }

    /**
     * Instantiate an instantiable configuration value.
     *
     * @param mixed $value
     *
     * @return object
     */
    protected function instantiateConfigValue($value)
    {
        if (is_string($value) && class_exists($value)) {
            return $this->app->make($value);
        }

        return $value;
    }
}

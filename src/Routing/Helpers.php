<?php

namespace Dingo\Api\Routing;

use ErrorException;

trait Helpers
{
    /**
     * Array of controller method properties.
     *
     * @var array
     */
    protected $methodProperties = [
        'scopes' => [],
        'providers' => [],
        'rateLimit' => [],
        'throttles' => [],
    ];

    /**
     * Throttles for controller methods.
     *
     * @param string|\Dingo\Api\Http\RateLimit\Throttle\Throttle $throttle
     * @param array                                              $options
     *
     * @return void
     */
    protected function throttle($throttle, array $options = [])
    {
        $this->methodProperties['throttles'][] = array_merge($this->methodProperties['throttles'], compact('throttle', 'options'));
    }

    /**
     * Rate limit controller methods.
     *
     * @param int   $limit
     * @param int   $expires
     * @param array $options
     *
     * @return void
     */
    protected function rateLimit($limit, $expires, array $options = [])
    {
        $this->methodProperties['rateLimit'][] = compact('limit', 'expires', 'options');
    }

    /**
     * Protect controller methods.
     *
     * @param array $options
     *
     * @return void
     */
    protected function protect(array $options = [])
    {
        $this->methodProperties['protected'][] = compact('options');
    }

    /**
     * Unprotect controller methods.
     *
     * @param array $options
     *
     * @return void
     */
    protected function unprotect(array $options = [])
    {
        $this->methodProperties['unprotected'][] = compact('options');
    }

    /**
     * Add scopes to controller methods.
     *
     * @param string|array $scopes
     * @param array        $options
     *
     * @return void
     */
    protected function scopes($scopes, array $options = [])
    {
        $scopes = $this->propertyValue($scopes);

        $this->methodProperties['scopes'][] = compact('scopes', 'options');
    }

    /**
     * Authenticate with certain providers on controller methods.
     *
     * @param string|array $providers
     * @param array        $options
     *
     * @return void
     */
    protected function authenticateWith($providers, array $options = [])
    {
        $providers = $this->propertyValue($providers);

        $this->methodProperties['providers'][] = compact('providers', 'options');
    }

    /**
     * Prepare a property value.
     *
     * @param string|array $value
     *
     * @return array
     */
    protected function propertyValue($value)
    {
        return is_string($value) ? explode('|', $value) : $value;
    }

    /**
     * Get controller method properties.
     *
     * @return array
     */
    public function getMethodProperties()
    {
        return $this->methodProperties;
    }

    /**
     * Get the internal dispatcher instance.
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function api()
    {
        return app('Dingo\Api\Dispatcher');
    }

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
     * Get the response factory instance.
     *
     * @return \Dingo\Api\Http\Response\Factory
     */
    protected function response()
    {
        return app('Dingo\Api\Http\Response\Factory');
    }

    /**
     * Magically handle calls to certain properties.
     *
     * @param string $key
     *
     * @throws \ErrorException
     *
     * @return mixed
     */
    public function __get($key)
    {
        $callable = [
            'api', 'user', 'auth', 'response',
        ];

        if (in_array($key, $callable) && method_exists($this, $key)) {
            return $this->$key();
        }

        throw new ErrorException('Undefined property '.get_class($this).'::'.$key);
    }

    /**
     * Magically handle calls to certain methods on the response factory.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     * @return \Dingo\Api\Http\Response
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->response(), $method) || $method == 'array') {
            return call_user_func_array([$this->response(), $method], $parameters);
        }

        throw new ErrorException('Undefined method '.get_class($this).'::'.$method);
    }
}

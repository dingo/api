<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseFactory;

trait ControllerTrait
{
    /**
     * API dispatcher instance.
     *
     * @var \Dingo\Api\Dispatcher
     */
    protected $api;

    /**
     * API authenticator instance.
     *
     * @var \Dingo\Api\Auth\Authenticator
     */
    protected $auth;

    /**
     * API response factory instance.
     *
     * @var \Dingo\Api\Http\ResponseFactory
     */
    protected $response;

    /**
     * Array of controller method properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Set the scopes for all or a subset of methods.
     *
     * @param string|array $scopes
     * @param string|array $methods
     *
     * @return \Illuminate\Routing\Controller
     */
    protected function scopes($scopes, $methods = null)
    {
        $scopes = $this->preparePropertyValue($scopes);

        if (is_null($methods)) {
            $this->properties['*']['scopes'] = $scopes;
        } else {
            foreach ($this->preparePropertyValue($methods) as $method) {
                $this->properties[$method]['scopes'] = $scopes;
            }
        }

        return $this;
    }

    /**
     * Unprotect controller methods.
     *
     * @param string|array $methods
     *
     * @return \Illuminate\Routing\Controller
     */
    protected function unprotect($methods = null)
    {
        return $this->setProtection($methods, false);
    }

    /**
     * Protect controller methods.
     *
     * @param string|array $methods
     *
     * @return \Illuminate\Routing\Controller
     */
    protected function protect($methods = null)
    {
        return $this->setProtection($methods, true);
    }

    /**
     * Set the protection of given methods.
     *
     * @param string|array $methods
     * @param bool         $protection
     *
     * @return \Illuminate\Routing\Controller
     */
    protected function setProtection($methods = null, $protection = true)
    {
        if (is_null($methods)) {
            $this->properties['*']['protected'] = $protection;
        } else {
            foreach ($this->preparePropertyValue($methods) as $method) {
                $this->properties[$method]['protected'] = $protection;
            }
        }

        return $this;
    }

    /**
     * Prepare a property value.
     *
     * @param string|array $value
     *
     * @return array
     */
    protected function preparePropertyValue($value)
    {
        return is_string($value) ? explode('|', $value) : $value;
    }

    /**
     * Get the controller method properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set the API dispatcher instance.
     *
     * @param \Dingo\Api\Dispatcher $api
     *
     * @return void
     */
    public function setDispatcher(Dispatcher $api)
    {
        $this->api = $api;
    }

    /**
     * Set the API authenticator instance.
     *
     * @param \Dingo\Api\Auth\Authenticator $auth
     *
     * @return void
     */
    public function setAuthenticator(Authenticator $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Set the API response factory instance.
     *
     * @param \Dingo\Api\Http\ResponseFactory $response
     *
     * @return void
     */
    public function setResponseFactory(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Magically handle calls to the response builder.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->response, $method)) {
            return call_user_func_array([$this->response, $method], $parameters);
        }

        return parent::__call($method, $parameters);
    }
}

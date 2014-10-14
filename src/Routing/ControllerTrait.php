<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\ResponseFactory;
use Illuminate\Routing\Controller as IlluminateController;

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
     * Array of unprotected controller methods.
     *
     * @var array
     */
    protected $unprotected = [];

    /**
     * Array of protected controller methods.
     *
     * @var array
     */
    protected $protected = [];

    /**
     * Array of controller method scopes.
     *
     * @var array
     */
    protected $scopedMethods = [];

    /**
     * Set the scopes for all or a subset of methods.
     *
     * @param  string|array  $scopes
     * @param  string|array  $methods
     * @return \Dingo\Api\Routing\Controller
     */
    protected function scope($scopes, $methods = null)
    {
        if (is_null($methods)) {
            $this->scopedMethods['*'] = (array) $scopes;
        } else {
            foreach ((array) $methods as $method) {
                $this->scopedMethods[$method] = (array) $scopes;
            }
        }

        return $this;
    }

    /**
     * Unprotect controller methods.
     *
     * @param  array  $methods
     * @return \Dingo\Api\Routing\Controller
     */
    protected function unprotect($methods)
    {
        $this->unprotected = array_merge($this->unprotected, is_array($methods) ? $methods : func_get_args());

        return $this;
    }

    /**
     * Protect controller methods.
     *
     * @param  array  $methods
     * @return \Dingo\Api\Routing\Controller
     */
    protected function protect($methods)
    {
        $this->protected = array_merge($this->protected, is_array($methods) ? $methods : func_get_args());

        return $this;
    }

    /**
     * Get the protected controller methods.
     *
     * @return array
     */
    public function getProtectedMethods()
    {
        return $this->protected;
    }

    /**
     * Get the unprotected controller methods.
     *
     * @return array
     */
    public function getUnprotectedMethods()
    {
        return $this->unprotected;
    }

    /**
     * Get the scoped methods.
     *
     * @return array
     */
    public function getScopedMethods()
    {
        return $this->scopedMethods;
    }

    /**
     * Set the API dispatcher instance.
     *
     * @param  \Dingo\Api\Dispatcher  $api
     * @return void
     */
    public function setDispatcher(Dispatcher $api)
    {
        $this->api = $api;
    }

    /**
     * Set the API authenticator instance.
     *
     * @param  \Dingo\Api\Auth\Authenticator  $auth
     * @return void
     */
    public function setAuthenticator(Authenticator $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Set the API response factory instance.
     *
     * @param  \Dingo\Api\Http\ResponseFactory  $response
     * @return void
     */
    public function setResponseFactory(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Magically handle calls to the response builder.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->response, $method)) {
            return call_user_func_array([$this->response, $method], $parameters);
        } elseif (method_exists($this, '__call')) {
            return $this->__call($method, $parameters);
        }
    }
}

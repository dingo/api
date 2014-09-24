<?php

namespace Dingo\Api\Routing;

use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Shield;
use Dingo\Api\Http\ResponseBuilder;
use Illuminate\Routing\Controller as IlluminateController;

abstract class Controller extends IlluminateController
{
    /**
     * API dispatcher instance.
     *
     * @var \Dingo\Api\Dispatcher
     */
    protected $api;

    /**
     * API authentication shield instance.
     *
     * @var \Dingo\Api\Auth\Shield
     */
    protected $auth;

    /**
     * API response builder instance.
     *
     * @var \Dingo\Api\Http\ResponseBuilder
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
     * Create a new controller instance.
     *
     * @param  \Dingo\Api\Dispatcher  $api
     * @param  \Dingo\Api\Auth\Shield  $auth
     * @param  \Dingo\Api\Http\ResponseBuilder  $response
     * @return void
     */
    public function __construct(Dispatcher $api, Shield $auth, ResponseBuilder $response)
    {
        $this->api = $api;
        $this->auth = $auth;
        $this->response = $response;
    }

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
        }

        return parent::__call($method, $parameters);
    }
}

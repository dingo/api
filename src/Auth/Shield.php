<?php

namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Shield
{
    /**
     * Illuminate auth instance.
     *
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * Illuminate application container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of authentication providers.
     *
     * @var array
     */
    protected $providers;

    /**
     * The provider used for authentication.
     *
     * @var \Dingo\Api\Auth\Provider
     */
    protected $providerUsed;

    /**
     * Authenticated user instance.
     *
     * @var \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * Illuminate request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Illuminate route instance.
     *
     * @var \Illumimate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Dingo\Api\Authentication instance.
     *
     * @param  \Illuminate\Auth\AuthManager  $auth
     * @param  \Illuminate\Container\Container  $container
     * @param  array  $providers
     * @return void
     */
    public function __construct(AuthManager $auth, Container $container, array $providers)
    {
        $this->auth = $auth;
        $this->container = $container;
        $this->providers = $providers;
    }

    /**
     * Authenticate the current request.
     *
     * @return null|\Dingo\Api\Http\Response
     */
    public function authenticate()
    {
        if (! $this->request || ! $this->route) {
            return;
        }

        $exceptionStack = [];

        // Spin through each of the registered authentication providers and attempt to
        // authenticate through one of them. This allows a developer to implement
        // and allow a number of different authentication mechanisms.
        foreach ($this->providers as $key => $provider) {
            try {
                $user = $provider->authenticate($this->request, $this->route);

                $this->providerUsed = $provider;

                return $this->user = $user;
            } catch (UnauthorizedHttpException $exception) {
                $exceptionStack[] = $exception;
            } catch (BadRequestHttpException $exception) {
                // We won't add this exception to the stack as it's thrown when the provider
                // is unable to authenticate due to the correct authorization header not
                // being set. We will throw an exception for this below.
            }
        }

        $exception = array_shift($exceptionStack);

        if ($exception === null) {
            $exception = new UnauthorizedHttpException(null, 'Failed to authenticate because of bad credentials or an invalid authorization header.');
        }

        throw $exception;
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model|null
     */
    public function getUser()
    {
        if ($this->user) {
            return $this->user;
        }

        try {
            return $this->user = $this->authenticate();
        } catch (Exception $exception) {
            return;
        }
    }

    /**
     * Alias for getUser.
     *
     * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
     */
    public function user()
    {
        return $this->getUser();
    }

    /**
     * Set the authenticated user.
     *
     * @param  \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model  $user
     * @return \Dingo\Api\Authentication
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Check if a user has authenticated with the API.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Get the provider used for authentication.
     *
     * @return \Dingo\Api\Auth\Provider
     */
    public function getProviderUsed()
    {
        return $this->providerUsed;
    }

    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Dingo\Api\Auth\Shield
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the route instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Dingo\Api\Auth\Shield
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Extend the authentication layer with a custom provider.
     *
     * @param  string  $key
     * @param  object|callable  $provider
     * @return void
     */
    public function extend($key, $provider)
    {
        $this->providers[$key] = is_callable($provider) ? call_user_func($provider, $this->container) : $provider;
    }
}

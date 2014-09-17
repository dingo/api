<?php

namespace Dingo\Api\Auth;

use Exception;
use Dingo\Api\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Authenticator
{
    /**
     * API router instance,
     * 
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * Illuminate application container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of available authentication providers.
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
     * Create a new authenticator instance.
     *
     * @param  \Dingo\Api\Routing\Router  $router
     * @param  \Illuminate\Container\Container  $container
     * @param  array  $providers
     * @return void
     */
    public function __construct(Router $router, Container $container, array $providers)
    {
        $this->router = $router;
        $this->container = $container;
        $this->providers = $this->prepareProviders($providers);
    }

    /**
     * Prepare the available authentication providers.
     * 
     * @param  array  $providers
     * @return array
     */
    protected function prepareProviders(array $providers)
    {
        foreach ($providers as $key => $provider) {
            $providers[$key] = $this->createProvider($provider);
        }

        return $providers;
    }

    /**
     * Create an authentication provider.
     * 
     * @param  mixed  $provider
     * @return mixed
     */
    protected function createProvider($provider)
    {
        return is_callable($provider) ? call_user_func($provider, $this->container) : $provider;
    }

    /**
     * Authenticate the current request.
     *
     * @param  array  $providers
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function authenticate(array $providers = [])
    {
        $exceptionStack = [];

        // Spin through each of the registered authentication providers and attempt to
        // authenticate through one of them. This allows a developer to implement
        // and allow a number of different authentication mechanisms.
        foreach ($this->filterProviders($providers) as $provider) {
            try {
                $user = $provider->authenticate($this->router->getCurrentRequest(), $this->router->getCurrentRoute());

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

        $this->throwUnauthorizedException($exceptionStack);
    }

    /**
     * Throw the first exception from the exception stack.
     * 
     * @param  array  $exceptionStack
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    protected function throwUnauthorizedException(array $exceptionStack)
    {
        $exception = array_shift($exceptionStack);

        if ($exception === null) {
            $exception = new UnauthorizedHttpException(null, 'Failed to authenticate because of bad credentials or an invalid authorization header.');
        }

        throw $exception;
    }

    /**
     * Filter the requested providers from the available providers.
     * 
     * @param  array  $providers
     * @return array
     */
    protected function filterProviders(array $providers)
    {
        return array_intersect_key($this->providers, array_flip($providers));
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
            return null;
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
        return ! is_null($this->user);
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
     * Extend the authentication layer with a custom provider.
     *
     * @param  string  $key
     * @param  object|callable  $provider
     * @return void
     */
    public function extend($key, $provider)
    {
        $this->providers[$key] = $this->createProvider($provider);
    }
}

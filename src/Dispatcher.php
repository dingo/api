<?php

namespace Dingo\Api;

use RuntimeException;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Auth\GenericUser;
use Dingo\Api\Auth\Authenticator;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Dispatcher
{
    /**
     * Illuminate request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Illuminate url generator instance.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * API router instance.
     *
     * @var \Dingo\Api\Routing\Router
     */
    protected $router;

    /**
     * API authentication Authenticator instance.
     *
     * @var \Dingo\Api\Auth\Authenticator
     */
    protected $auth;

    /**
     * API config instance.
     *
     * @var \Dingo\Api\Config
     */
    protected $config;

    /**
     * Internal request stack.
     *
     * @var array
     */
    protected $requestStack = [];

    /**
     * Internal route stack.
     *
     * @var array
     */
    protected $routeStack = [];

    /**
     * Version for the request.
     *
     * @var string
     */
    protected $version;

    /**
     * Request parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Request uploaded files.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Indicates whether the authenticated user is persisted.
     *
     * @var bool
     */
    protected $persistAuthenticatedUser = true;

    /**
     * Create a new dispatcher instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @param  \Dingo\Api\Routing\Router  $router
     * @param  \Dingo\Api\Auth\Authenticator  $auth
     * @param  \Dingo\Api\Config  $config
     * @return void
     */
    public function __construct(Request $request, UrlGenerator $url, Router $router, Authenticator $auth, Config $config)
    {
        $this->request = $request;
        $this->url = $url;
        $this->router = $router;
        $this->auth = $auth;
        $this->config = $config;

        $this->setupRequestStack();
    }

    /**
     * Setup the request stack by cloning the initial request.
     *
     * @return void
     */
    protected function setupRequestStack()
    {
        $this->requestStack[] = clone $this->request;
    }

    /**
     * Attach files to be uploaded.
     *
     * @param  array  $files
     * @return \Dingo\Api\Dispatcher
     */
    public function attach(array $files)
    {
        foreach ($files as $key => $path) {
            // Create a new instance of UploadedFile if not supplied with one
            $this->files[$key] = $path instanceof UploadedFile ? $path : new UploadedFile($path, basename($path));
        }

        return $this;
    }

    /**
     * Internal request will be authenticated as the given user.
     *
     * @param  \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model  $user
     * @return \Dingo\Api\Dispatcher
     * @throws \RuntimeException
     */
    public function be($user)
    {
        if (! $user instanceof Model && ! $user instanceof GenericUser) {
            throw new RuntimeException('User must be an instance of either Illuminate\Database\Eloquent\Model or Illuminate\Auth\GenericUser.');
        }

        $this->auth->setUser($user);

        return $this;
    }

    /**
     * Only authenticate with the given user for a single request.
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function once()
    {
        $this->persistAuthenticatedUser = false;

        return $this;
    }

    /**
     * Set the version of the API for the next request.
     *
     * @param  string  $version
     * @return \Dingo\Api\Dispatcher
     */
    public function version($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the parameters to be sent on the next API request.
     *
     * @param  string|array  $parameters
     * @return \Dingo\Api\Dispatcher
     */
    public function with($parameters)
    {
        $this->parameters = array_merge($this->parameters, is_array($parameters) ? $parameters : func_get_args());

        return $this;
    }

    /**
     * Perform an API request to a named route.
     *
     * @param  string  $name
     * @param  string|array  $routeParameters
     * @param  string|array  $parameters
     * @return mixed
     */
    public function route($name, $routeParameters = [], $parameters = [])
    {
        $version = $this->version ?: $this->config->getVersion();

        $route = $this->router->getApiVersions()->get($version)->getByName($name);

        $uri = ltrim($this->url->route($name, $routeParameters, false, $route), '/');

        return $this->queueRequest($route->methods()[0], $uri, $parameters);
    }

    /**
     * Perform an API request to a controller action.
     *
     * @param  string  $action
     * @param  string|array  $actionParameters
     * @param  string|array  $parameters
     * @return mixed
     */
    public function action($action, $actionParameters = [], $parameters = [])
    {
        $version = $this->version ?: $this->config->getVersion();

        $route = $this->router->getApiVersions()->get($version)->getByAction($action);

        $uri = ltrim($this->url->route($action, $actionParameters, false, $route), '/');

        return $this->queueRequest($route->methods()[0], $uri, $parameters);
    }

    /**
     * Perform API GET request.
     *
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    public function get($uri, $parameters = [])
    {
        return $this->queueRequest('get', $uri, $parameters);
    }

    /**
     * Perform API POST request.
     *
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    public function post($uri, $parameters = [])
    {
        return $this->queueRequest('post', $uri, $parameters);
    }

    /**
     * Perform API PUT request.
     *
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    public function put($uri, $parameters = [])
    {
        return $this->queueRequest('put', $uri, $parameters);
    }

    /**
     * Perform API PATCH request.
     *
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    public function patch($uri, $parameters = [])
    {
        return $this->queueRequest('patch', $uri, $parameters);
    }

    /**
     * Perform API DELETE request.
     *
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    public function delete($uri, $parameters = [])
    {
        return $this->queueRequest('delete', $uri, $parameters);
    }

    /**
     * Queue up and dispatch a new request.
     *
     * @param  string  $verb
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return mixed
     */
    protected function queueRequest($verb, $uri, $parameters)
    {
        $request = $this->requestStack[] = $this->createRequest($verb, $uri, $parameters);

        $this->request->replace($request->input());
        $this->request->files->replace($request->file());

        return $this->dispatch($request);
    }

    /**
     * Create a new internal request from an HTTP verb and URI.
     *
     * @param  string  $verb
     * @param  string  $uri
     * @param  string|array  $parameters
     * @return \Dingo\Api\Http\InternalRequest
     */
    protected function createRequest($verb, $uri, $parameters)
    {
        if (! isset($this->version)) {
            $this->version = $this->config->getVersion();
        }

        // Once we have a version we can go ahead and grab the API collection,
        // if one exists, from the router.
        $api = $this->router->getApiVersions()->get($this->version);

        if (($prefix = $api->option('prefix')) && ! starts_with($uri, $prefix)) {
            $uri = sprintf('%s/%s', $prefix, $uri);
        }

        $parameters = array_merge($this->parameters, (array) $parameters);

        $request = InternalRequest::create($uri, $verb, $parameters, [], $this->files);

        if ($domain = $api->option('domain')) {
            $request->headers->set('host', $domain);
        }

        $request->headers->set('accept', $this->buildAcceptHeader());

        return $request;
    }

    /**
     * Build the "Accept" header.
     *
     * @return string
     */
    protected function buildAcceptHeader()
    {
        return sprintf('application/vnd.%s.%s+%s', $this->config->getVendor(), $this->version, $this->config->getFormat());
    }

    /**
     * Attempt to dispatch an internal request.
     *
     * @param  \Dingo\Api\Http\InternalRequest  $request
     * @return mixed
     * @throws \Exception|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
     */
    protected function dispatch(InternalRequest $request)
    {
        $this->routeStack[] = $this->router->getCurrentRoute();

        try {
            $response = $this->router->dispatch($request);

            if (! $response->isSuccessful()) {
                throw new HttpException($response->getStatusCode(), $response->getOriginalContent());
            }
        } catch (HttpExceptionInterface $exception) {
            $this->refreshRequestStack();

            throw $exception;
        }

        $this->refreshRequestStack();

        return $response->getOriginalContent();
    }

    /**
     * Refresh the request stack by resetting the authentication,
     * popping the last request from the stack, replacing the
     * input, and resetting the version and parameters.
     *
     * @return void
     */
    protected function refreshRequestStack()
    {
        if (! $this->persistAuthenticatedUser) {
            $this->auth->setUser(null);

            $this->persistAuthenticatedUser = true;
        }

        if ($route = array_pop($this->routeStack)) {
            $this->router->setCurrentRoute($route);
        }

        $this->replaceRequestInput();

        $this->version = null;

        $this->parameters = $this->files = [];
    }

    /**
     * Replace the request input with the previous request input.
     *
     * @return void
     */
    protected function replaceRequestInput()
    {
        array_pop($this->requestStack);

        $previous = end($this->requestStack);

        $this->router->setCurrentRequest($previous);

        $this->request->replace($previous->input());
    }
}

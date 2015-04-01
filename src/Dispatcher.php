<?php

namespace Dingo\Api;

use RuntimeException;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use Illuminate\Auth\GenericUser;
use Dingo\Api\Auth\Authenticator;
use Illuminate\Container\Container;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Cookie;
use Dingo\Api\Exception\InternalHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Dispatcher
{
    /**
     * Illuminate container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

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
     * API properties instance.
     *
     * @var \Dingo\Api\Properties
     */
    protected $properties;

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
     * Request headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Request cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Request parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Request raw content.
     *
     * @var string
     */
    protected $content;

    /**
     * Request uploaded files.
     *
     * @var array
     */
    protected $uploads = [];

    /**
     * Domain for the request.
     *
     * @var string
     */
    protected $domain;

    /**
     * Indicates whether the returned response is the raw response object.
     *
     * @var bool
     */
    protected $raw = false;

    /**
     * Indicates whether authentication is persisted.
     *
     * @var bool
     */
    protected $persistAuthentication = true;

    /**
     * Create a new dispatcher instance.
     *
     * @param \Illuminate\Container\Container   $container
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Illuminate\Routing\UrlGenerator  $url
     * @param \Dingo\Api\Routing\Router         $router
     * @param \Dingo\Api\Auth\Authenticator     $auth
     * @param \Dingo\Api\Properties             $properties
     *
     * @return void
     */
    public function __construct(Container $container, Filesystem $files, UrlGenerator $url, Router $router, Authenticator $auth, Properties $properties)
    {
        $this->container = $container;
        $this->files = $files;
        $this->url = $url;
        $this->router = $router;
        $this->auth = $auth;
        $this->properties = $properties;

        $this->setupRequestStack();
    }

    /**
     * Setup the request stack by cloning the initial request.
     *
     * @return void
     */
    protected function setupRequestStack()
    {
        $this->requestStack[] = clone $this->container['request'];
    }

    /**
     * Attach files to be uploaded.
     *
     * @param array $files
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function attach(array $files)
    {
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $file = new UploadedFile($file['path'], basename($file['path']), $file['mime'], $file['size']);
            } elseif (is_string($file)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                $file = new UploadedFile($file, basename($file), finfo_file($finfo, $file), $this->files->size($file));
            } elseif (! $file instanceof UploadedFile) {
                continue;
            }

            $this->uploads[$key] = $file;
        }

        return $this;
    }

    /**
     * Internal request will be authenticated as the given user.
     *
     * @param \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model $user
     *
     * @throws \RuntimeException
     *
     * @return \Dingo\Api\Dispatcher
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
     * Send a JSON payload in the request body.
     *
     * @param string|array $content
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function json($content)
    {
        if (is_array($content)) {
            $content = json_encode($content);
        }

        $this->content = $content;

        return $this->header('content-type', 'application/json');
    }

    /**
     * Sets the domain to be used for the request.
     *
     * @param string $domain
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function on($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Return the raw response object once request is dispatched.
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function raw()
    {
        $this->raw = true;

        return $this;
    }

    /**
     * Only authenticate with the given user for a single request.
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function once()
    {
        $this->persistAuthentication = false;

        return $this;
    }

    /**
     * Set the version of the API for the next request.
     *
     * @param string $version
     *
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
     * @param string|array $parameters
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function with($parameters)
    {
        $this->parameters = array_merge($this->parameters, is_array($parameters) ? $parameters : func_get_args());

        return $this;
    }

    /**
     * Set a header to be sent on the next API request.
     *
     * @param string $key
     * @param string $value
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function header($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Set a cookie to be sent on the next API request.
     *
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     *
     * @return \Dingo\Api\Dispatcher
     */
    public function cookie(Cookie $cookie)
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    /**
     * Perform an API request to a named route.
     *
     * @param string       $name
     * @param string|array $parameters
     * @param string|array $requestParameters
     *
     * @return mixed
     */
    public function route($name, $parameters = [], $requestParameters = [])
    {
        $version = $this->version ?: $this->properties->getVersion();

        $route = $this->router->getApiGroups()->getByDomainOrVersion($this->domain, $version)->getByName($name);

        return $this->queueRouteOrActionRequest($route, $name, $parameters, $requestParameters);
    }

    /**
     * Perform an API request to a controller action.
     *
     * @param string       $action
     * @param string|array $parameters
     * @param string|array $requestParameters
     *
     * @return mixed
     */
    public function action($action, $parameters = [], $requestParameters = [])
    {
        $version = $this->version ?: $this->properties->getVersion();

        $route = $this->router->getApiGroups()->getByDomainOrVersion($this->domain, $version)->getByAction($action);

        return $this->queueRouteOrActionRequest($route, $action, $parameters, $requestParameters);
    }

    /**
     * Perform API GET request.
     *
     * @param string       $uri
     * @param string|array $parameters
     *
     * @return mixed
     */
    public function get($uri, $parameters = [])
    {
        return $this->queueRequest('get', $uri, $parameters);
    }

    /**
     * Perform API POST request.
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function post($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('post', $uri, $parameters, $content);
    }

    /**
     * Perform API PUT request.
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function put($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('put', $uri, $parameters, $content);
    }

    /**
     * Perform API PATCH request.
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function patch($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('patch', $uri, $parameters, $content);
    }

    /**
     * Perform API DELETE request.
     *
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    public function delete($uri, $parameters = [], $content = '')
    {
        return $this->queueRequest('delete', $uri, $parameters, $content);
    }

    /**
     * Queue up and dispatch a new request to a route name or action.
     *
     * @param \Dingo\Api\Routing\Route $route
     * @param string                   $name
     * @param string|array             $parameters
     * @param string|array             $requestParameters
     *
     * @return mixed
     */
    protected function queueRouteOrActionRequest($route, $name, $parameters, $requestParameters)
    {
        $uri = ltrim($this->url->route($name, $parameters, false, $route), '/');

        return $this->queueRequest($route->methods()[0], $uri, $requestParameters);
    }

    /**
     * Queue up and dispatch a new request.
     *
     * @param string       $verb
     * @param string       $uri
     * @param string|array $parameters
     * @param string       $content
     *
     * @return mixed
     */
    protected function queueRequest($verb, $uri, $parameters, $content = '')
    {
        if ($content != '') {
            $this->content = $content;
        }

        $this->container->instance('request', $this->requestStack[] = $this->createRequest($verb, $uri, $parameters));

        return $this->dispatch($this->container['request']);
    }

    /**
     * Create a new internal request from an HTTP verb and URI.
     *
     * @param string       $verb
     * @param string       $uri
     * @param string|array $parameters
     *
     * @return \Dingo\Api\Http\InternalRequest
     */
    protected function createRequest($verb, $uri, $parameters)
    {
        if (! isset($this->version)) {
            $this->version = $this->properties->getVersion();
        }

        $api = $this->router->getApiGroups()->getByDomainOrVersion($this->domain, $this->version);

        if (($prefix = $api->option('prefix')) && ! starts_with($uri, $prefix)) {
            $uri = sprintf('%s/%s', $prefix, $uri);
        }

        $parameters = array_merge($this->parameters, (array) $parameters);

        $request = InternalRequest::create($this->url->to($uri), $verb, $parameters, $this->cookies, $this->uploads, [], $this->content);

        if ($domain = $api->option('domain')) {
            $request->headers->set('host', $domain);
        }

        foreach ($this->headers as $header => $value) {
            $request->headers->set($header, $value);
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
        return sprintf('application/vnd.%s.%s+%s', $this->properties->getVendor(), $this->version, $this->properties->getFormat());
    }

    /**
     * Attempt to dispatch an internal request.
     *
     * @param \Dingo\Api\Http\InternalRequest $request
     *
     * @throws \Exception|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
     *
     * @return mixed
     */
    protected function dispatch(InternalRequest $request)
    {
        $this->routeStack[] = $this->router->getCurrentRoute();

        $this->clearCachedFacadeInstance();

        try {
            $response = $this->router->dispatch($request);

            if (! $response->isSuccessful()) {
                throw new InternalHttpException($response);
            } elseif (! $this->raw) {
                $response = $response->getOriginalContent();
            }
        } catch (HttpExceptionInterface $exception) {
            $this->refreshRequestStack();

            throw $exception;
        }

        $this->refreshRequestStack();

        return $response;
    }

    /**
     * Refresh the request stack.
     *
     * This is done by resetting the authentication, popping
     * the last request from the stack, replacing the input,
     * and resetting the version and parameters.
     *
     * @return void
     */
    protected function refreshRequestStack()
    {
        if (! $this->persistAuthentication) {
            $this->auth->setUser(null);

            $this->persistAuthentication = true;
        }

        if ($route = array_pop($this->routeStack)) {
            $this->router->setCurrentRoute($route);
        }

        $this->replaceRequestInstance();

        $this->clearCachedFacadeInstance();

        $this->raw = false;

        $this->version = $this->domain = $this->content = null;

        $this->parameters = $this->uploads = [];
    }

    /**
     * Replace the request instance with the previous request instance.
     *
     * @return void
     */
    protected function replaceRequestInstance()
    {
        array_pop($this->requestStack);

        $this->container->instance('request', end($this->requestStack));

        $this->router->setCurrentRequest($this->container['request']);
    }

    /**
     * Clear the cached facade instance.
     *
     * @return void
     */
    protected function clearCachedFacadeInstance()
    {
        // Facades cache the resolved instance so we need to clear out the
        // request instance that may have been cached. Otherwise we'll
        // may get unexpected results.
        RequestFacade::clearResolvedInstance('request');
    }
}

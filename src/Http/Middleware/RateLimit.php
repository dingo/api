<?php

namespace Dingo\Api\Http\Middleware;

use Dingo\Api\Http\Response;
use Illuminate\Container\Container;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimit implements HttpKernelInterface
{
    /**
     * The wrapped kernel implementation.
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $app;

    /**
     * Laravel application container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Rate limiting config.
     *
     * @var array
     */
    protected $config = [
        'authenticated' => [
            'limit' => 6000,
            'reset' => 3600
        ],
        'unauthenticated' => [
            'limit' => 60,
            'reset' => 3600
        ],
        'exceeded' => 'API rate limit has been exceeded.'
    ];

    /**
     * Array of resolved container bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Array of binding mappings.
     *
     * @var array
     */
    protected $mappings = ['auth' => 'dingo.api.auth'];

    /**
     * Indicates if the request is authenticated.
     *
     * @var bool
     */
    protected $authenticatedRequest;

    /**
     * Create a new rate limit middleware instance.
     *
     * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(HttpKernelInterface $app, Container $container)
    {
        $this->app = $app;
        $this->container = $container;
    }

    /**
     * Handle a given request and return the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  int  $type
     * @param  bool  $catch
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->container->boot();

        $this->prepareConfig($request);

        // Internal requests as well as requests that are not targetting the
        // API will not be rate limited. We'll also be sure not to perform
        // any rate limiting if it has been disabled.
        if ($request instanceof InternalRequest or ! $this->router->requestTargettingApi($request) or $this->rateLimitingDisabled()) {
            return $this->app->handle($request, $type, $catch);
        }

        $this->cache->add($this->config['keys']['requests'], 0, $this->config['reset']);
        $this->cache->add($this->config['keys']['reset'], time() + ($this->config['reset'] * 60), $this->config['reset']);
        $this->cache->increment($this->config['keys']['requests']);

        if ($this->exceededRateLimit()) {
            list ($version, $format) = $this->router->parseAcceptHeader($request);

            $response = (new Response(['message' => $this->config['exceeded']], 403))->morph($format);
        } else {
            $response = $this->app->handle($request, $type, $catch);
        }

        return $this->adjustResponseHeaders($response);
    }

    /**
     * Adjust the response headers and return the response.
     *
     * @param  \Dingo\Api\Http\Response  $response
     * @return \Dingo\Api\Http\Response
     */
    protected function adjustResponseHeaders($response)
    {
        $requestsRemaining = $this->config['limit'] - $this->cache->get($this->config['keys']['requests']);

        if ($requestsRemaining < 0) {
            $requestsRemaining = 0;
        }

        $response->headers->set('X-RateLimit-Limit', $this->config['limit']);
        $response->headers->set('X-RateLimit-Remaining', $requestsRemaining);
        $response->headers->set('X-RateLimit-Reset', $this->cache->get($this->config['keys']['reset']));

        return $response;
    }

    /**
     * Determine if the client has exceeded their rate limit.
     *
     * @return bool
     */
    protected function exceededRateLimit()
    {
        return $this->cache->get($this->config['keys']['requests']) > $this->config['limit'];
    }

    /**
     * Deteremine if the request is authenticated.
     *
     * @return bool
     */
    protected function isAuthenticatedRequest()
    {
        if (! is_null($this->authenticatedRequest)) {
            return $this->authenticatedRequest;
        }

        return $this->authenticatedRequest = $this->auth->check();
    }

    /**
     * Prepare the configuration for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function prepareConfig($request)
    {
        $this->config = array_merge($this->config, $this->container->make('config')->get('api::rate_limiting'));

        if ($this->isAuthenticatedRequest()) {
            $this->config = array_merge(['exceeded' => $this->config['exceeded']], $this->config['authenticated']);
        } else {
            $this->config = array_merge(['exceeded' => $this->config['exceeded']], $this->config['unauthenticated']);
        }

        $this->config['keys']['requests'] = sprintf('dingo:api:requests:%s', $request->getClientIp());
        $this->config['keys']['reset'] = sprintf('dingo:api:reset:%s', $request->getClientIp());
    }

    /**
     * Determine if rate limiting is disabled.
     *
     * @return bool
     */
    protected function rateLimitingDisabled()
    {
        return $this->config['limit'] == 0;
    }

    /**
     * Dynamically handle binding calls on the container.
     *
     * @param  string  $binding
     * @return mixed
     */
    public function __get($binding)
    {
        $binding = isset($this->mappings[$binding]) ? $this->mappings[$binding] : $binding;

        if (isset($this->bindings[$binding])) {
            return $this->bindings[$binding];
        }

        return $this->bindings[$binding] = $this->container->make($binding);
    }
}

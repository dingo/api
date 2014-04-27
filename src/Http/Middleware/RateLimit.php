<?php namespace Dingo\Api\Http\Middleware;

use Dingo\Api\Http\Response;
use Illuminate\Container\Container;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimit implements HttpKernelInterface {

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
	 * Default rate limiting config.
	 * 
	 * @var array
	 */
	protected $defaultConfig = [
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
		// Our middleware needs to ensure that Laravel is booted before we
		// can do anything. This gives us access to all the booted
		// service providers and other container bindings.
		$this->container->boot();

		$this->defaultConfig = array_merge($this->defaultConfig, $this->config->get('api::rate_limiting'));

		// Internal requests as well as requests that are not targetting the
		// API will not be rate limited. We'll also be sure not to perform
		// any rate limiting if it has been disabled.
		if ($request instanceof InternalRequest or ! $this->router->requestTargettingApi($request) or $this->rateLimitingDisabled())
		{
			return $this->app->handle($request, $type, $catch);
		}

		$cacheKeys = $this->getCacheKeys($request);

		$this->cache->add($cacheKeys['limit'], 0, $this->getCacheReset());
		$this->cache->add($cacheKeys['reset'], time() + ($this->getCacheReset() * 60), $this->getCacheReset());

		$this->cache->increment($cacheKeys['limit']);

		// If the total number of requests made exceeds the allowed number of
		// requests then we'll create a new API response with a 403 status
		// code. This will inform the consumer they have breached their
		// allowed limit and must wait until it is reset.
		$allowedRequests = $this->getAllowedRequests();
		$totalRequests = $this->cache->get($cacheKeys['limit']);

		if ($totalRequests > $allowedRequests)
		{
			$response = new Response($this->defaultConfig['exceeded'], 403);

			$response->morph();
		}

		// Otherwise we'll let the next middleware handle the request and
		// use the response returned from that.
		else
		{
			$response = $this->app->handle($request, $type, $catch);
		}

		$requestsRemaining = $allowedRequests - $totalRequests;

		$response->headers->set('X-RateLimit-Limit', $allowedRequests);
		$response->headers->set('X-RateLimit-Remaining', $requestsRemaining > 0 ? $requestsRemaining : 0);
		$response->headers->set('X-RateLimit-Reset', $this->cache->get($cacheKeys['reset']));

		return $response;
	}

	/**
	 * Get the allowed number of requests.
	 * 
	 * @return int
	 */
	protected function getAllowedRequests()
	{
		return $this->shield->check() ? $this->defaultConfig['authenticated']['limit'] : $this->defaultConfig['unauthenticated']['limit'];
	}

	/**
	 * Determine if rate limiting is disabled.
	 * 
	 * @return bool
	 */
	protected function rateLimitingDisabled()
	{
		return $this->getAllowedRequests() == 0;
	}

	/**
	 * Get the cache reset time.
	 * 
	 * @return int
	 */
	protected function getCacheReset()
	{

		return $this->shield->check() ? $this->defaultConfig['authenticated']['reset'] : $this->defaultConfig['unauthenticated']['reset'];
	}

	/**
	 * Get the "limit" and "reset" cache keys.
	 * 
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return array
	 */
	protected function getCacheKeys(Request $request)
	{
		return [
			'limit' => sprintf('dingo:api:limit:%s', $request->getClientIp()),
			'reset' => sprintf('dingo:api:reset:%s', $request->getClientIp())
		];
	}

	/**
	 * Dynamically handle binding calls on the container.
	 * 
	 * @param  string  $binding
	 * @return mixed
	 */
	public function __get($binding)
	{
		$mappings = ['shield' => 'dingo.api.auth'];
		$binding = isset($mappings[$binding]) ? $mappings[$binding] : $binding;

		if (isset($this->bindings[$binding])) return $this->bindings[$binding];

		return $this->bindings[$binding] = $this->container->make($binding);
	}

}

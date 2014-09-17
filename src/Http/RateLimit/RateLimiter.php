<?php

namespace Dingo\Api\Http\RateLimit;

use Illuminate\Http\Request;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;

class RateLimiter
{
    /**
     * Illuminate cache instance.
     * 
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Illuminate container instance.
     * 
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Registered throttles.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $throttles;

    /**
     * Throttle used for rate limiting.
     * 
     * @var \Dingo\Api\Http\RateLimit\Throttle
     */
    protected $throttle;

    /**
     * Illuminate request instance being throttled.
     * 
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new rate limiter instance.
     * 
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @param  \Illuminate\Container\Container  $container
     * @param  array  $throttles
     * @return void
     */
    public function __construct(CacheManager $cache, Container $container, array $throttles)
    {
        $this->cache = $cache;
        $this->container = $container;
        $this->throttles = new Collection($throttles);
    }

    /**
     * Execute the rate limiting for the given request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $limit
     * @param  int  $expires
     * @return void
     */
    public function rateLimitRequest(Request $request, $limit = 0, $expires = 0)
    {
        $this->request = $request;

        // If the developer specified a certain amount of requests or expiration
        // time on a specific route then we'll always use the route specific
        // throttle with the given values.
        if ($limit > 0 || $expires > 0) {
            $this->throttle = new RouteSpecificThrottle(['limit' => $limit, 'expires' => $expires]);
        
        // Otherwise we'll use the throttle that gives the consumer the largest
        // amount of requests. If no matching throttle is found then rate
        // limiting will not be imposed for the request.
        } else {
            $this->throttle = $this->getMatchingThrottles()->sort(function ($a, $b) {
                return $a->getRequests() < $b->getRequests();
            })->first();
        }

        if (is_null($this->throttle)) {
            return;
        }

        $this->cache('requests', 0, $this->throttle->getExpires());
        $this->cache('expires', time() + ($this->throttle->getExpires() * 60), $this->throttle->getExpires());
        $this->increment('requests');
    }

    /**
     * Determine if the rate limit has been exceeded.
     * 
     * @return bool
     */
    public function exceededRateLimit()
    {
        return $this->retrieve('requests') > $this->throttle->getLimit();
    }

    /**
     * Get matching throttles after executing the condition of each throttle.
     * 
     * @return \Illuminate\Support\Collection
     */
    protected function getMatchingThrottles()
    {
        return $this->throttles->filter(function ($throttle) {
            return $throttle->match($this->container);
        });
    }

    /**
     * Namespace a cache key.
     * 
     * @param  string  $key
     * @return string
     */
    protected function key($key)
    {
        return sprintf('dingo.api.%s.%s', $key, $this->request->getClientIp());
    }

    /**
     * Cache a value under a given key for a certain amount of minutes.
     * 
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @return void
     */
    protected function cache($key, $value, $minutes)
    {
        $this->cache->add($this->key($key), $value, $minutes);
    }

    /**
     * Retrieve a value from the cache store.
     * 
     * @param  string  $key
     * @return mixed
     */
    protected function retrieve($key)
    {
        return $this->cache->get($this->key($key));
    }

    /**
     * Increment a key in the cache.
     * 
     * @param  string  $key
     * @return void
     */
    protected function increment($key)
    {
        $this->cache->increment($this->key($key));
    }

    /**
     * Determine if the request was rate limited.
     * 
     * @return bool
     */
    public function requestWasRateLimited()
    {
        return ! is_null($this->throttle);
    }

    /**
     * Get the throttle used to rate limit the request.
     * 
     * @return \Dingo\Api\Http\RateLimit\Throttle
     */
    public function getThrottle()
    {
        return $this->throttle;
    }

    /**
     * Get the remaining limit before the consumer is rate limited.
     * 
     * @return int
     */
    public function getRemainingLimit()
    {
        $remaining = $this->throttle->getLimit() - $this->retrieve('requests');

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get the timestamp for when the current rate limiting will expire.
     * 
     * @return int
     */
    public function getRateLimitExpiration()
    {
        return $this->retrieve('expires');
    }
}

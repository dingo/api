<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use RuntimeException;
use Dingo\Api\Properties;
use Dingo\Api\Http\Matcher;
use Dingo\Api\Http\Request;

class RequestMiddleware
{
    /**
     * HTTP Matcher instance.
     *
     * @var \Dingo\Api\Http\Matcher
     */
    protected $matcher;

    /**
     * Create a new request middlware instance.
     *
     * @param \Dingo\Api\Http\Matcher $matcher
     *
     * @return void
     */
    public function __construct(Matcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->matcher->matchDomain($request) || $this->matcher->matchPrefix($request)) {
            $request = Request::createFromExisting($request);
        }

        return $next($request);
    }

    protected function throwExceptionOnInvalidInitialization()
    {
        if (is_null($this->properties->getPrefix()) && is_null($this->properties->getDomain())) {
            throw new RuntimeException('Invalid initialization of API package, a prefix or domain is required.');
        }
    }
}

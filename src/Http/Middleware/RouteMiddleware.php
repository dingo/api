<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Routing\Router;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Events\Dispatcher as EventDispatcher;

class RouteMiddleware
{
    public function __construct(Router $router, EventDispatcher $events)
    {
        $this->router = $router;
        $this->events = $events;
    }

    public function handle($request, Closure $next)
    {
        list($version, $format) = $this->parseAcceptHeader($request);

        $this->currentVersion = $version;
        $this->currentFormat = $format;

        $this->container->instance('Illuminate\Http\Request', $request);

        try {
            $response = $next($request);

            // Attempt to get the formatter so that we can catch and handle
            // any exceptions due to a poorly formatted accept header.
            $response->getFormatter($format);
        } catch (Exception $exception) {
            $response = $this->handleException($request, $exception);
        }

        // This goes hand in hand with the above. We'll check to see if a
        // formatter exists for the requested response format. If not
        // then we'll revert to the default format because we are
        // most likely formatting an error response.
        if (! $raw) {
            if (! $response->hasFormatter($format)) {
                $format = $this->properties->getFormat();
            }

            $response->getFormatter($format)
                     ->setRequest($request)
                     ->setResponse($response);

            $response = $response->morph($format);
        }

        return $response;
    }

    /**
     * Handle an exception thrown during a routes execution.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleException(Request $request, Exception $exception)
    {
        if ($request instanceof InternalRequest) {
            throw $exception;
        } else {
            $response = $this->events->until('router.exception', [$exception]);

            if ()

            // When an exception is thrown it halts execution of the dispatch. We'll
            // call the attached after filters for caught exceptions still.
            $this->callFilter('after', $request, $response);
        }

        return $response;
    }
}

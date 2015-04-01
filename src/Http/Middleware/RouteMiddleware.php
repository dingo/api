<?php

namespace Dingo\Api\Http\Middleware;

use Closure;
use Exception;
use Dingo\Api\Http\Request;
use Dingo\Api\Http\Response;
use Illuminate\Routing\Router;
use Dingo\Api\Http\InternalRequest;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RouteMiddleware
{
    public function __construct(Router $router, EventDispatcher $events)
    {
        $this->router = $router;
        $this->events = $events;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Dingo\Api\Http\Request $request
     * @param \Closure                $next
     *
     * @return \Dingo\Api\Http\Response
     */
    public function handle($request, Closure $next)
    {
        $format = 'json';

        try {
            $response = $this->prepareResponse($request, $next($request));

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
        //if (! $raw) {
            if (! $response->hasFormatter($format)) {
                $format = $this->properties->getFormat();
            }

            $response->getFormatter($format)
                     ->setRequest($request)
                     ->setResponse($response);

            $response = $response->morph($format);
        //}

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
        }

        return $response;
    }

    /**
     * Prepare a response instance.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed                                     $response
     *
     * @return \Dingo\Api\Http\Response
     */
    protected function prepareResponse($request, $response)
    {
        if (! $response instanceof Response) {
            $response = Response::makeFromExisting($response);
        }

        return $response->prepare($request);
    }
}

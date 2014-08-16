<?php

namespace Dingo\Api\Events;

use Exception;
use Dingo\Api\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Routing\ControllerReviser;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RouterHandler
{
    protected $router;

    protected $exceptionHandler;
    
    protected $controllerReviser;

    public function __construct(Router $router, Handler $exceptionHandler, ControllerReviser $controllerReviser)
    {
        $this->router = $router;
        $this->exceptionHandler = $exceptionHandler;
        $this->controllerReviser = $controllerReviser;
    }

    public function handleControllerRevising(Route $route, Request $request)
    {
        if ($this->router->requestTargettingApi($request)) {
            $this->controllerReviser->revise($route);
        }
    }
    
    public function handleException(Exception $exception)
    {
        if ($this->exceptionHandler->willHandle($exception)) {
            $response = $this->exceptionHandler->handle($exception);

            return Response::makeFromExisting($response);
        } elseif (! $exception instanceof HttpExceptionInterface) {
            throw $exception;
        }

        if (! $message = $exception->getMessage()) {
            $message = sprintf('%d %s', $exception->getStatusCode(), Response::$statusTexts[$exception->getStatusCode()]);
        }

        $response = ['message' => $message];

        if ($exception instanceof ResourceException and $exception->hasErrors()) {
            $response['errors'] = $exception->getErrors();
        }

        if ($code = $exception->getCode()) {
            $response['code'] = $code;
        }

        return new Response($response, $exception->getStatusCode(), $exception->getHeaders());
    }
}

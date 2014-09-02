<?php

namespace Dingo\Api\Events;

use Exception;
use Dingo\Api\Http\Response;
use Dingo\Api\Exception\Handler;
use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionHandler
{
    /**
     * API exception handler instance.
     * 
     * @var \Dingo\Api\Exception\Handler
     */
    protected $handler;

    /**
     * Create a new exception handler instance.
     * 
     * @param  \Dingo\Api\Exception\Handler  $handler
     * @return void
     */
    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Handle an exception thrown during dispatching of an API request.
     * 
     * @param  \Exception  $exception
     * @return \Dingo\Api\Http\Response
     * @throws \Exception
     */
    public function handle(Exception $exception)
    {
        if ($this->handler->willHandle($exception)) {
            $response = $this->handler->handle($exception);

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

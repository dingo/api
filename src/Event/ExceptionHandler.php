<?php

namespace Dingo\Api\Event;

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
     * Indicates if debug mode is enabled.
     *
     * @var bool
     */
    protected $debug;

    /**
     * Create a new exception handler instance.
     *
     * @param \Dingo\Api\Exception\Handler $handler
     * @param bool                         $debug
     *
     * @return void
     */
    public function __construct(Handler $handler, $debug = false)
    {
        $this->handler = $handler;
        $this->debug = $debug;
    }

    /**
     * Handle an exception thrown during dispatching of an API request.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     *
     * @return \Dingo\Api\Http\Response
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

        $response = ['message' => $message, 'status_code' => $exception->getStatusCode()];

        if ($exception instanceof ResourceException && $exception->hasErrors()) {
            $response['errors'] = $exception->getErrors();
        }

        if ($code = $exception->getCode()) {
            $response['code'] = $code;
        }

        if ($this->debug) {
            $response['debug'] = [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'class' => get_class($exception),
                'trace' => explode("\n", $exception->getTraceAsString())
            ];
        }

        return new Response($response, $exception->getStatusCode(), $exception->getHeaders());
    }

    /**
     * Enable or disable debug mode.
     *
     * @param bool $debug
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }
}

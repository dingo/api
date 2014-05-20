<?php namespace Dingo\Api;

use ReflectionFunction;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionHandler
{

    /**
     * Array of exception handlers.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Register a new exception handler.
     *
     * @param  callable $callback
     * @return void
     */
    public function register(callable $callback)
    {
        $hint = $this->handlerHint($callback);

        $this->handlers[$hint] = $callback;
    }

    /**
     * Handle an exception if it has an existing handler.
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception
     * @return \Illuminate\Http\Response
     */
    public function handle(HttpExceptionInterface $exception)
    {
        foreach ($this->handlers as $hint => $handler) {
            if ($exception instanceof $hint) {
                $response = call_user_func($handler, $exception);

                if (!$response instanceof Response) {
                    $response = new Response($response, $exception->getStatusCode());
                }

                return $response;
            }
        }
    }

    /**
     * Determine if the handler will handle the given exception.
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception
     * @return bool
     */
    public function willHandle(HttpExceptionInterface $exception)
    {
        return (bool)array_first($this->handlers, function ($hint) use ($exception) {
            return $exception instanceof $hint;
        }, false);
    }

    /**
     * Get the hint for an exception handler.
     *
     * @param  callable $callback
     * @return string
     */
    protected function handlerHint(callable $callback)
    {
        $reflection = new ReflectionFunction($callback);

        $exception = $reflection->getParameters()[0];

        return $exception->getClass()->getName();
    }

    /**
     * Get the exception handlers.
     *
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

}

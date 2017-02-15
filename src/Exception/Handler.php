<?php

namespace Dingo\Api\Exception;

use Exception;
use ReflectionFunction;
use Illuminate\Http\Response;
use Dingo\Api\Contract\Debug\ExceptionHandler;
use Dingo\Api\Contract\Debug\MessageBagErrors;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandler;

class Handler implements ExceptionHandler, IlluminateExceptionHandler
{
    /**
     * Array of exception handlers.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Generic response format.
     *
     * @var array
     */
    protected $format;

    /**
     * Indicates if we are in debug mode.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * User defined replacements to merge with defaults.
     *
     * @var array
     */
    protected $replacements = [];

    /**
     * The parent Illuminate exception handler instance.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $parentHandler;

    /**
     * Create a new exception handler instance.
     *
     * @param \Illuminate\Contracts\Debug\ExceptionHandler $parentHandler
     * @param array                                        $format
     * @param bool                                         $debug
     *
     * @return void
     */
    public function __construct(IlluminateExceptionHandler $parentHandler, array $format, $debug)
    {
        $this->parentHandler = $parentHandler;
        $this->format = $format;
        $this->debug = $debug;
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public function report(Exception $exception)
    {
        $this->parentHandler->report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Dingo\Api\Http\Request $request
     * @param \Exception              $exception
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function render($request, Exception $exception)
    {
        return $this->handle($exception);
    }

    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception                                        $exception
     *
     * @return mixed
     */
    public function renderForConsole($output, Exception $exception)
    {
        return $this->parentHandler->renderForConsole($output, $exception);
    }

    /**
     * Register a new exception handler.
     *
     * @param callable $callback
     *
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
     * @param \Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Exception $exception)
    {
        foreach ($this->handlers as $hint => $handler) {
            if (! $exception instanceof $hint) {
                continue;
            }

            if ($response = $handler($exception)) {
                if (! $response instanceof BaseResponse) {
                    $response = new Response($response, $this->getExceptionStatusCode($exception));
                }

                return $response;
            }
        }

        return $this->genericResponse($exception);
    }

    /**
     * Handle a generic error response if there is no handler available.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\Response
     */
    protected function genericResponse(Exception $exception)
    {
        $replacements = $this->prepareReplacements($exception);

        $response = $this->newResponseArray();

        array_walk_recursive($response, function (&$value, $key) use ($exception, $replacements) {
            if (starts_with($value, ':') && isset($replacements[$value])) {
                $value = $replacements[$value];
            }
        });

        $response = $this->recursivelyRemoveEmptyReplacements($response);

        return new Response($response, $this->getStatusCode($exception), $this->getHeaders($exception));
    }

    /**
     * Get the status code from the exception.
     *
     * @param \Exception $exception
     *
     * @return int
     */
    protected function getStatusCode(Exception $exception)
    {
        return $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
    }

    /**
     * Get the headers from the exception.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    protected function getHeaders(Exception $exception)
    {
        return $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
    }

    /**
     * Prepare the replacements array by gathering the keys and values.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    protected function prepareReplacements(Exception $exception)
    {
        $statusCode = $this->getStatusCode($exception);

        if (! $message = $exception->getMessage()) {
            $message = sprintf('%d %s', $statusCode, Response::$statusTexts[$statusCode]);
        }

        $replacements = [
            ':message' => $message,
            ':status_code' => $statusCode,
        ];

        if ($exception instanceof MessageBagErrors && $exception->hasErrors()) {
            $replacements[':errors'] = $exception->getErrors();
        }

        if ($code = $exception->getCode()) {
            $replacements[':code'] = $code;
        }

        if ($this->runningInDebugMode()) {
            $replacements[':debug'] = [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'class' => get_class($exception),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return array_merge($replacements, $this->replacements);
    }

    /**
     * Set user defined replacements.
     *
     * @param array $replacements
     *
     * @return void
     */
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;
    }

    /**
     * Recursirvely remove any empty replacement values in the response array.
     *
     * @param array $input
     *
     * @return array
     */
    protected function recursivelyRemoveEmptyReplacements(array $input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->recursivelyRemoveEmptyReplacements($value);
            }
        }

        return array_filter($input, function ($value) {
            if (is_string($value)) {
                return ! starts_with($value, ':');
            }

            return true;
        });
    }

    /**
     * Create a new response array with replacement values.
     *
     * @return array
     */
    protected function newResponseArray()
    {
        return $this->format;
    }

    /**
     * Get the exception status code.
     *
     * @param \Exception $exception
     * @param int        $defaultStatusCode
     *
     * @return int
     */
    protected function getExceptionStatusCode(Exception $exception, $defaultStatusCode = 500)
    {
        return ($exception instanceof HttpExceptionInterface) ? $exception->getStatusCode() : $defaultStatusCode;
    }

    /**
     * Determines if we are running in debug mode.
     *
     * @return bool
     */
    protected function runningInDebugMode()
    {
        return $this->debug;
    }

    /**
     * Get the hint for an exception handler.
     *
     * @param callable $callback
     *
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

    /**
     * Set the error format array.
     *
     * @param array $format
     *
     * @return void
     */
    public function setErrorFormat(array $format)
    {
        $this->format = $format;
    }

    /**
     * Set the debug mode.
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

<?php

namespace Dingo\Api\Http\Response;

use Closure;
use ErrorException;
use Illuminate\Support\Str;
use Dingo\Api\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Dingo\Api\Transformer\Factory as TransformerFactory;

class Factory
{
    /**
     * Transformer factory instance.
     *
     * @var \Dingo\Api\Transformer\Factory
     */
    protected $transformer;

    /**
     * Create a new response factory instance.
     *
     * @param \Dingo\Api\Transformer\Factory $transformer
     *
     * @return void
     */
    public function __construct(TransformerFactory $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Respond with a created response and associate a location if provided.
     *
     * @param null|string $location
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function created($location = null)
    {
        $response = new Response(null);
        $response->setStatusCode(201);

        if (! is_null($location)) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a no content response.
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function noContent()
    {
        $response = new Response(null);

        return $response->setStatusCode(204);
    }

    /**
     * Bind a collection to a transformer and start building a response.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param object                         $transformer
     * @param array                          $parameters
     * @param \Closure                       $after
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function collection(Collection $collection, $transformer, array $parameters = [], Closure $after = null)
    {
        if ($collection->isEmpty()) {
            $class = get_class($collection);
        } else {
            $class = get_class($collection->first());
        }

        $binding = $this->transformer->register($class, $transformer, $parameters, $after);

        return new Response($collection, 200, [], $binding);
    }

    /**
     * Bind an item to a transformer and start building a response.
     *
     * @param object   $item
     * @param object   $transformer
     * @param array    $parameters
     * @param \Closure $after
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function item($item, $transformer, array $parameters = [], Closure $after = null)
    {
        $class = get_class($item);

        $binding = $this->transformer->register($class, $transformer, $parameters, $after);

        return new Response($item, 200, [], $binding);
    }

    /**
     * Bind a paginator to a transformer and start building a response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param object                                      $transformer
     * @param array                                       $parameters
     * @param \Closure                                    $after
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function paginator(LengthAwarePaginator $paginator, $transformer, array $parameters = [], Closure $after = null)
    {
        if ($paginator->isEmpty()) {
            $class = get_class($paginator);
        } else {
            $class = get_class($paginator->first());
        }

        $binding = $this->transformer->register($class, $transformer, $parameters, $after);

        return new Response($paginator->getCollection(), 200, [], $binding);
    }


    /**
     * Return an error response.
     *
     * @param string|array $error
     * @param int          $statusCode
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function error($error, $statusCode)
    {
        if (! is_array($error)) {
            $error = ['message' => $error];
        }

        $error = array_merge(['status_code'  => $statusCode], $error);

        return $this->array($error)->setStatusCode($statusCode);
    }

    /**
     * Return a 404 not found error.
     *
     * @param string|array $message
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function errorNotFound($message = 'Not Found')
    {
        return $this->error($message, 404);
    }

    /**
     * Return a 400 bad request error.
     *
     * @param string|array $message
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function errorBadRequest($message = 'Bad Request')
    {
        return $this->error($message, 400);
    }

    /**
     * Return a 403 forbidden error.
     *
     * @param string|array $message
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function errorForbidden($message = 'Forbidden')
    {
        return $this->error($message, 403);
    }

    /**
     * Return a 500 internal server error.
     *
     * @param string|array $message
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function errorInternal($message = 'Internal Error')
    {
        return $this->error($message, 500);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param string|array $message
     *
     * @return \Dingo\Api\Http\Response\Builder
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }

    /**
     * Call magic methods beginning with "with".
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'with')) {
            return call_user_func_array([$this, Str::camel(substr($method, 4))], $parameters);

        // Because PHP won't let us name the method "array" we'll simply watch for it
        // in here and return the new binding. Gross.
        } elseif ($method == 'array') {
            return new Response($parameters[0]);
        }

        throw new ErrorException('Undefined method '.get_class($this).'::'.$method);
    }
}

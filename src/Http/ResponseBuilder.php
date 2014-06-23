<?php

namespace Dingo\Api\Http;

use League\Fractal\Manager as Fractal;
use Dingo\Api\Transformer\FractalTransformer;
use League\Fractal\Resource\Item as FractalItem;
use Illuminate\Http\Response as IlluminateResponse;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Pagination\CursorInterface as FractalCursorInterface;
use League\Fractal\Resource\ResourceInterface as FractalResourceInterface;
use League\Fractal\Pagination\PaginatorInterface as FractalPaginatorInterface;

class ResponseBuilder
{
    /**
     * Fractral transformer instance.
     *
     * @var \Dingo\Api\Transformer\FractalTransformer
     */
    protected $fractal;

    /**
     * The HTTP response headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The HTTP response status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Create a new response builder instance.
     *
     * @param  \Dingo\Api\Transformer\FractalTransformer  $fractal
     * @return void
     */
    public function __construct(FractalTransformer $fractal)
    {
        $this->fractal = $fractal;
    }

    /**
     * Create a new collection resource with the given tranformer.
     *
     * @param  array|object  $collection
     * @param  object  $transformer
     * @param  \League\Fractal\Pagination\CursorInterface  $cursor
     * @return \Illuminate\Http\Response
     */
    public function withCollection($collection, $transformer, FractalCursorInterface $cursor = null)
    {
        $resource = new FractalCollection($collection, $transformer);

        if (! is_null($cursor)) {
            $resource->setCursor($cursor);
        }

        return $this->build($resource);
    }

    /**
     * Create a new item resource with the given transformer.
     *
     * @param  array|object  $item
     * @param  object  $transformer
     * @return \Illuminate\Http\Response
     */
    public function withItem($item, $transformer)
    {
        $resource = new FractalItem($item, $transformer);

        return $this->build($resource);
    }

    /**
     * Create a new collection resource from a paginator with the given transformer.
     *
     * @param  \League\Fractal\Pagination\PaginatorInterface  $paginator
     * @param  object  $transformer
     * @return \Illuminate\Http\Response
     */
    public function withPaginator(FractalPaginatorInterface $paginator, $transformer)
    {
        $resource = new FractalCollection($paginator->getCollection(), $transformer);

        $resource->setPaginator($paginator);

        return $this->build($resource);
    }

    /**
     * Return an array response.
     *
     * @param  array  $array
     * @return \Illuminate\Http\Response
     */
    public function withArray(array $array)
    {
        return $this->build($array);
    }

    /**
     * Return an error response.
     * 
     * @param  string|array  $error
     * @param  int  $statusCode
     * @return \Illuminate\Http\Response
     */
    public function withError($error, $statusCode)
    {
        if (! is_array($error)) {
            $error = ['error' => $error];
        }

        $error = array_merge(['status_code'  => $statusCode], $error);

        return $this->setStatusCode($statusCode)->withArray($error);
    }

    /**
     * Build the response.
     *
     * @param  array|\League\Fractal\Resource\ResourceInterface  $data
     * @return \Illuminate\Http\Response
     */
    protected function build($data)
    {
        if ($data instanceof FractalResourceInterface) {
            $data = $this->resolveFractal()->createData($data)->toArray();
        }

        return new IlluminateResponse($data, $this->statusCode, $this->headers);
    }

    /**
     * Add a header to the response
     *
     * @param  string  $name
     * @param  string  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add an array of headers.
     * 
     * @param  array  $headers
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Add a header to the response.
     * 
     * @param  string  $name
     * @param  string  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function header($name, $value)
    {
        return $this->addHeader($name, $value);
    }

    /**
     * Add an array of headers.
     * 
     * @param  array  $headers
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function headers(array $headers)
    {
        return $this->addHeaders($headers);
    }

    /**
     * Set the responses status code.
     *
     * @param  int  $statusCode
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Return a 404 not found error.
     * 
     * @param  string|array  $message
     * @return \Illuminate\Http\Response
     */
    public function errorNotFound($message = 'Not Found')
    {
        return $this->withError($message, 404);
    }

    /**
     * Return a 400 bad request error.
     * 
     * @param  string|array  $message
     * @return \Illuminate\Http\Response
     */
    public function errorBadRequest($message = 'Bad Request')
    {
        return $this->withError($message, 400);
    }

    /**
     * Return a 403 forbidden error.
     * 
     * @param  string|array  $message
     * @return \Illuminate\Http\Response
     */
    public function errorForbidden($message = 'Forbidden')
    {
        return $this->withError($message, 403);
    }

    /**
     * Return a 500 internal server error.
     * 
     * @param  string|array  $message
     * @return \Illuminate\Http\Response
     */
    public function errorInternal($message = 'Internal Error')
    {
        return $this->withError($message, 500);
    }

    /**
     * Return a 401 unauthorized error.
     * 
     * @param  string|array  $message
     * @return \Illuminate\Http\Response
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->withError($message, 401);
    }

    /**
     * Resolve the Fractal manager.
     *
     * @return \League\Fractal\Manager
     */
    protected function resolveFractal()
    {
        $this->fractal->parseFractalIncludes();

        return $this->fractal->getFractal();
    }
}

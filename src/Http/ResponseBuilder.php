<?php

namespace Dingo\Api\Http;

use Dingo\Api\Transformer\FractalTransformer;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Serializer\SerializerAbstract;
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
     * The Fractal serializer.
     *
     * @var \League\Fractal\Serializer\SerializerAbstract
     */
    protected $serializer;

    /**
     * Array of meta data.
     *
     * @var array
     */
    protected $meta = [];

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
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function withCollection($collection, $transformer, FractalCursorInterface $cursor = null, $key = null)
    {
        $resource = new FractalCollection($collection, $transformer, $key);

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
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function withItem($item, $transformer, $key = null)
    {
        $resource = new FractalItem($item, $transformer, $key);

        return $this->build($resource);
    }

    /**
     * Create a new collection resource from a paginator with the given transformer.
     *
     * @param  \League\Fractal\Pagination\PaginatorInterface  $paginator
     * @param  object  $transformer
     * @param  string  $key
     * @return \Illuminate\Http\Response
     */
    public function withPaginator(FractalPaginatorInterface $paginator, $transformer, $key = null)
    {
        $resource = new FractalCollection($paginator->getCollection(), $transformer, $key);

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
     * Add a Fractal meta key and value pair.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addMeta($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Add a Fractal meta key and value pair.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function meta($key, $value)
    {
        return $this->addMeta($key, $value);
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
            $fractal = $this->resolveFractal();

            foreach ($this->meta as $key => $value) {
                $data->setMetaValue($key, $value);
            }

            if ($this->serializer) {
                $fractal->setSerializer($this->serializer);
            }

            $data = $this->resolveFractal()->createData($data)->toArray();
        }

        $response = new IlluminateResponse($data, $this->statusCode, $this->headers);

        $this->reset();

        return $response;
    }

    /**
     * Reset this response builder instance.
     *
     * @return void
     */
    protected function reset()
    {
        $this->serializer = null;
        $this->statusCode = 200;
        $this->headers = [];
        $this->meta = [];
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
     * Set the Fractal serializer.
     *
     * @param  \League\Fractal\Serializer\SerializerAbstract  $serializer
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setSerializer(SerializerAbstract $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Set the Fractal serializer.
     *
     * @param  \League\Fractal\Serializer\SerializerAbstract  $serializer
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function serializer(SerializerAbstract $serializer)
    {
        return $this->setSerializer($serializer);
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

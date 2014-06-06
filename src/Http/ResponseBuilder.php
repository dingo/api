<?php

namespace Dingo\Api\Http;

use Illuminate\Container\Container;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use Illuminate\Http\Response as IlluminateResponse;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Pagination\CursorInterface as FractalCursorInterface;
use League\Fractal\Resource\ResourceInterface as FractalResourceInterface;
use League\Fractal\Pagination\PaginatorInterface as FractalPaginatorInterface;

class ResponseBuilder
{
    /**
     * Illuminate container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

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
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
     * Return an array response.
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
     * @param  string  $headerName
     * @param  string  $headerValue
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addHeader($headerName, $headerValue)
    {
        $this->headers[$headerName] = $headerValue;

        return $this;
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
     * Resolve the Fractal manager.
     *
     * @return void
     */
    protected function resolveFractal()
    {
        $transformer = $this->container->make('dingo.api.transformer');

        $transformer->parseFractalIncludes();

        return $transformer->getFractal();
    }
}

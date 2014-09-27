<?php

namespace Dingo\Api\Transformer;

use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Pagination\Paginator as IlluminatePaginator;
use League\Fractal\Resource\Collection as FractalCollection;

class FractalTransformer extends Transformer
{
    /**
     * Fractal manager instance.
     *
     * @var \League\Fractal\Manager
     */
    protected $fractal;

    /**
     * The include query string key.
     *
     * @var string
     */
    protected $includeKey;

    /**
     * The include separator.
     *
     * @var string
     */
    protected $includeSeparator;

    /**
     * Create a new fractal transformer instance.
     *
     * @param  \League\Fractal\Manager  $fractal
     * @param  string  $includeKey
     * @param  string  $includeSeparator
     * @return void
     */
    public function __construct(Fractal $fractal, $includeKey = 'include', $includeSeparator = ',')
    {
        $this->fractal = $fractal;
        $this->includeKey = $includeKey;
        $this->includeSeparator = $includeSeparator;
    }

    /**
     * Transform a response with a transformer.
     *
     * @param  string|object  $response
     * @param  object  $transformer
     * @return array
     */
    public function transformResponse($response, $transformer, $binding)
    {
        $this->parseFractalIncludes();

        $resource = $this->createResource($response, $transformer, $binding->getParameters());

        // If the response is a paginator then we'll create a new paginator
        // adapter for Laravel and set the paginator instance on our
        // collection resource.
        if ($response instanceof IlluminatePaginator) {
            $paginator = $this->createPaginatorAdapter($response);

            $resource->setPaginator($paginator);
        }

        foreach ($binding->getMeta() as $key => $value) {
            $resource->setMetaValue($key, $value);
        }

        $binding->fireCallback($resource);

        return $this->fractal->createData($resource)->toArray();
    }

    /**
     * Create the Fractal paginator adapter.
     *
     * @param  \Illuminate\Pagination\Paginator  $paginator
     * @return \League\Fractal\Pagination\IlluminatePaginatorAdapter
     */
    protected function createPaginatorAdapter(IlluminatePaginator $paginator)
    {
        return new IlluminatePaginatorAdapter($paginator);
    }

    /**
     * Create a Fractal resource instance.
     *
     * @param  mixed  $response
     * @param  \League\Fractal\TransformerAbstract  $transformer
     * @param  array  $parameters
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\Collection
     */
    protected function createResource($response, $transformer, array $parameters)
    {
        $key = isset($parameters['key']) ? $parameters['key'] : null;

        if ($response instanceof IlluminatePaginator || $response instanceof IlluminateCollection) {
            return new FractalCollection($response, $transformer, $key);
        }

        return new FractalItem($response, $transformer, $key);
    }

    /**
     * Parse includes.
     *
     * @return void
     */
    public function parseFractalIncludes()
    {
        $request = $this->getCurrentRequest();

        $includes = array_filter(explode($this->includeSeparator, $request->get($this->includeKey)));

        $this->fractal->parseIncludes($includes);
    }

    /**
     * Get the underlying Fractal instance.
     *
     * @return \League\Fractal\Manager
     */
    public function getFractal()
    {
        return $this->fractal;
    }
}

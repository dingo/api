<?php

namespace Dingo\Api\Transformer;

use Illuminate\Http\Request;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Pagination\Paginator as IlluminatePaginator;
use League\Fractal\Resource\Collection as FractalCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class FractalTransformer implements TransformerInterface
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
     * @param  mixed  $response
     * @param  object  $transformer
     * @param  \Dingo\Api\Transformer\Binding  $binding
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request)
    {
        $this->parseFractalIncludes($request);

        $resource = $this->createResource($response, $transformer, $binding->getParameters());

        // If the response is a paginator then we'll create a new paginator
        // adapter for Laravel and set the paginator instance on our
        // collection resource.
        if ($response instanceof IlluminatePaginator) {
            $paginator = $this->createPaginatorAdapter($response);

            $resource->setPaginator($paginator);
        }

        if ($response instanceof EloquentCollection) {
            $response->load($this->fractal->getRequestedIncludes());
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
     * Parse the includes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function parseFractalIncludes(Request $request)
    {
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

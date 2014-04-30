<?php namespace Dingo\Api;

use Illuminate\Container\Container;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use Illuminate\Support\Collection as IlluminateCollection;
use League\Fractal\Resource\Collection as FractalCollection;

class Transformer {

	/**
	 * Fractal manager instance.
	 * 
	 * @var \League\Fractal\Manager
	 */
	protected $fractal;

	/**
	 * Illuminate container instance.
	 * 
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Array of registered transformers.
	 * 
	 * @var array
	 */
	protected $transformers = [];

	/**
	 * Create a new transformer instance.
	 * 
	 * @param  \League\Fractal\Manager  $fractal
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Fractal $fractal, Container $container)
	{
		$this->fractal = $fractal;
		$this->container = $container;
	}

	/**
	 * Register a transformer for a class.
	 * 
	 * @param  string  $class
	 * @param  string|\Closure  $transformer
	 * @return \Dingo\Api\Transformer
	 */
	public function transform($class, $transformer)
	{
		$this->transformers[$class] = $transformer;

		return $this;
	}

	/**
	 * Transform a response with a registered transformer.
	 * 
	 * @param  mixed  $response
	 * @return mixed
	 */
	public function transformResponse($response)
	{
		$transformer = $this->resolveTransformer($this->getTransformer($response));

		return $this->fractal->createData($this->createResource($response, $transformer))->toArray();
	}

	/**
	 * Determine if a response is transformable.
	 * 
	 * @param  mixed  $response
	 * @return bool
	 */
	public function transformableResponse($response)
	{
		return ! is_null($this->getTransformer($response));
	}

	/**
	 * Create a Fractal resource instance.
	 * 
	 * @param  mixed  $response
	 * @param  \League\Fractal\TransformerAbstract
	 * @return \League\Fractal\Resource\Item|\League|Fractal\Resource\Collection
	 */
	protected function createResource($response, $transformer)
	{
		if ($response instanceof IlluminateCollection)
		{
			return new FractalCollection($response, $transformer);
		}
		
		return new FractalItem($response, $transformer);
	}

	/**
	 * Resolve a transfomer instance.
	 * 
	 * @param  null|string|\Closure  $transformer
	 * @return \League\Fractal\TransformerAbstract
	 */
	protected function resolveTransformer($transformer)
	{
		if ( ! $transformer) return;

		if (is_string($transformer))
		{
			return new $transformer;
		}

		return call_user_func($transformer, $this->container);
	}

	/**
	 * Get transformer from a class.
	 * 
	 * @param  mixed  $class
	 * @return null|string|\Closure
	 */
	protected function getTransformer($class)
	{
		if ($class instanceof IlluminateCollection)
		{
			return $this->getTransformerFromCollection($class);
		}

		$class = is_object($class) ? get_class($class) : $class;

		return $this->hasTransformer($class) ? $this->transformers[$class] : null;
	}

	/**
	 * Determine if a class has a transformer.
	 * 
	 * @param  mixed  $class
	 * @return bool
	 */
	protected function hasTransformer($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return isset($this->transformers[$class]);
	}

	/**
	 * Get a registered transformer from a collection of items.
	 * 
	 * @param  \Illuminate\Support\Collection  $collection
	 * @return null|string|\Closure
	 */
	protected function getTransformerFromCollection($collection)
	{
		return $this->getTransformer($collection->first());
	}

	/**
	 * Get the array of registered transformers.
	 * 
	 * @return array
	 */
	public function getTransformers()
	{
		return $this->transformers;
	}


}

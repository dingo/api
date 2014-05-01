<?php namespace Dingo\Api;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use League\Fractal\Manager as Fractal;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Pagination\Paginator as IlluminatePaginator;
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
	 * The scopes query string key.
	 * 
	 * @var string
	 */
	protected $embedsKey;

	/**
	 * The scopes separator.
	 * 
	 * @var string
	 */
	protected $embedsSeparator;

	/**
	 * Array of registered transformers.
	 * 
	 * @var array
	 */
	protected $transformers = [];

	/**
	 * The current request instance.
	 * 
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Create a new transformer instance.
	 * 
	 * @param  \League\Fractal\Manager  $fractal
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Fractal $fractal, Container $container, $embedsKey = 'embeds', $embedsSeparator = ',')
	{
		$this->fractal = $fractal;
		$this->container = $container;
		$this->embedsKey = $embedsKey;
		$this->embedsSeparator = $embedsSeparator;
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

		$this->setRequestedScopes();

		$resource = $this->createResource($response, $transformer);

		// If the response is a paginator then we'll create a new paginator
		// adapter for Laravel and set the paginator instance on our
		// collection resource.
		if ($response instanceof IlluminatePaginator)
		{
			$paginator = new IlluminatePaginatorAdapter($response);

			$resource->setPaginator($paginator);
		}

		return $this->fractal->createData($resource)->toArray();
	}

	/**
	 * Determine if a response is transformable.
	 * 
	 * @param  mixed  $response
	 * @return bool
	 */
	public function transformableResponse($response)
	{
		return $this->hasTransformer($response);
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
		if ($this->isCollection($response))
		{
			return new FractalCollection($response, $transformer);
		}
		
		return new FractalItem($response, $transformer);
	}

	/**
	 * Resolve a transfomer instance.
	 * 
	 * @param  string|\Closure  $transformer
	 * @return \League\Fractal\TransformerAbstract
	 */
	protected function resolveTransformer($transformer)
	{
		if (is_string($transformer))
		{
			return $this->container->make($transformer);
		}

		return call_user_func($transformer, $this->container);
	}

	/**
	 * Get transformer from a class.
	 * 
	 * @param  mixed  $class
	 * @return string|\Closure
	 * @throws \RuntimeException
	 */
	protected function getTransformer($class)
	{
		if ($this->isCollection($class))
		{
			return $this->getTransformerFromCollection($class);
		}

		$class = is_object($class) ? get_class($class) : $class;

		if ( ! $this->hasTransformer($class))
		{
			throw new RuntimeException('Unable to find bound transformer for "'.$class.'" class.');
		}

		return $this->transformers[$class];
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
	 * Set the requested scopes.
	 * 
	 * @return void
	 */
	protected function setRequestedScopes()
	{
		if ( ! $this->request)
		{
			return;
		}

		$scopes = array_filter(explode($this->embedsSeparator, $this->request->get($this->embedsKey)));

		$this->fractal->setRequestedScopes($scopes);
	}

	/**
	 * Determine if the instance is a collection.
	 * 
	 * @param  object  $instance
	 * @return bool
	 */
	protected function isCollection($instance)
	{
		return $instance instanceof IlluminateCollection or $instance instanceof IlluminatePaginator;
	}

	/**
	 * Set the request that's being used to generate the response.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
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

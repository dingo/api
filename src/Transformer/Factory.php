<?php namespace Dingo\Api\Transformer;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;

class Factory {

	/**
	 * Illuminate container instance.
	 * 
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Transformer instance.
	 * 
	 * @var \Dingo\Api\Transformer\Transformer
	 */
	protected $transformer;

	/**
	 * Array of registered transformer bindings.
	 * 
	 * @var array
	 */
	protected $bindings = [];

	/**
	 * Create a new transformer factory instance.
	 * 
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Register a transformer binding resolver for a class.
	 * 
	 * @param  string  $class
	 * @param  string|callable|object  $resolver
	 * @return \Dingo\Api\Transformer\Factory
	 */
	public function transform($class, $resolver)
	{
		$this->bindings[$class] = $resolver;

		return $this;
	}

	/**
	 * Transform a response with a registered transformer.
	 * 
	 * @param  string|object  $response
	 * @return mixed
	 */
	public function transformResponse($response)
	{
		$transformer = $this->resolveTransformerBinding($this->getBinding($response));

		return $this->transformer->transformResponse($response, $transformer);
	}

	/**
	 * Determine if a response is transformable.
	 * 
	 * @param  mixed  $response
	 * @return bool
	 */
	public function transformableResponse($response)
	{
		return $this->transformableType($response) and ($this->hasBinding($response) or $this->boundByContract($response));
	}
		
	/**
	 * Deteremine if a value is of a transformable type.
	 * 
	 * @param  mixed  $value
	 * @return bool
	 */
	public function transformableType($value)
	{		
		return is_object($value) or is_string($value);
	}

	/**
	 * Resolve a transfomer binding instance.
	 * 
	 * @param  string|callable|object  $resolver
	 * @return mixed
	 */
	protected function resolveTransformerBinding($resolver)
	{
		if (is_string($resolver))
		{
			return $this->container->make($resolver);
		}
		elseif (is_callable($resolver))
		{
			return call_user_func($resolver, $this->container);
		}
		
		return $resolver;
	}

	/**
	 * Get a registered transformer binding.
	 * 
	 * @param  string|object  $class
	 * @return string|callable|object
	 * @throws \RuntimeException
	 */
	protected function getBinding($class)
	{
		if ($this->isCollection($class))
		{
			return $this->getBindingFromCollection($class);
		}

		if ($this->boundByContract($class))
		{
			return $class->getTransformer();
		}

		$class = is_object($class) ? get_class($class) : $class;

		if ( ! $this->hasBinding($class))
		{
			throw new RuntimeException('Unable to find bound transformer for "'.$class.'" class.');
		}

		return $this->bindings[$class];
	}

	/**
	 * Get a registered transformer binding from a collection of items.
	 * 
	 * @param  \Illuminate\Support\Collection  $collection
	 * @return null|string|callable
	 */
	protected function getBindingFromCollection($collection)
	{
		return $this->getBinding($collection->first());
	}

	/**
	 * Determine if a class has a transformer binding.
	 * 
	 * @param  string|object  $class
	 * @return bool
	 */
	protected function hasBinding($class)
	{
		if ($this->isCollection($class))
		{
			$class = $class->first();
		}

		$class = is_object($class) ? get_class($class) : $class;

		return isset($this->bindings[$class]);
	}

	/**
	 * Determine if the class is bound by the transformable contract.
	 * 
	 * @param  string|object  $class
	 * @return bool
	 */
	protected function boundByContract($class)
	{
		if ($this->isCollection($class))
		{
			$class = $class->first();
		}

		return is_object($class) and $class instanceof TransformableInterface;
	}

	/**
	 * Determine if the instance is a collection.
	 * 
	 * @param  object  $instance
	 * @return bool
	 */
	protected function isCollection($instance)
	{
		return $instance instanceof Collection or $instance instanceof Paginator;
	}

	/**
	 * Get the array of registered transformer bindings.
	 * 
	 * @return array
	 */
	public function getTransformerBindings()
	{
		return $this->bindings;
	}

	/**
	 * Set the transformer instance.
	 * 
	 * @param  \Dingo\Api\Transformer\Transformer  $transformer
	 * @return \Dingo\Api\Transformer\Factory
	 */
	public function setTransformer(Transformer $transformer)
	{
		$this->transformer = $transformer;

		return $this;
	}

	/**
	 * Set the request instance on the transformer.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		if ( ! $this->transformer)
		{
			throw new RuntimeException('Request cannot be set when no transformer has been registered.');
		}

		$this->transformer->setRequest($request);
	}

}

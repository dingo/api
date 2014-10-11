<?php

namespace Dingo\Api\Transformer;

use Closure;
use RuntimeException;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;

abstract class Transformer
{
    /**
     * Illuminate container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of registered transformer bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Get the current request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->container['router']->getCurrentRequest();
    }

    /**
     * Set the illuminate container instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a transformer binding resolver for a class.
     *
     * @param  string  $class
     * @param  string|callable|object  $resolver
     * @return \Dingo\Api\Transformer\Binding
     */
    public function register($class, $resolver, array $parameters = [], Closure $after = null)
    {
        return $this->bindings[$class] = $this->createBinding($resolver, $parameters, $after);
    }

    /**
     * Transform a response.
     *
     * @param  string|object  $response
     * @return mixed
     */
    public function transform($response)
    {
        $binding = $this->getBinding($response);

        return $this->transformResponse($response, $binding->resolveTransformer(), $binding);
    }

    /**
     * Transform a response with a transformer.
     *
     * @param  string|object  $response
     * @param  object  $transformer
     * @return mixed
     */
    abstract public function transformResponse($response, $transformer, $binding);

    /**
     * Determine if a response is transformable.
     *
     * @param  mixed  $response
     * @return bool
     */
    public function transformableResponse($response)
    {
        return $this->transformableType($response) && ($this->hasBinding($response) || $this->boundByContract($response));
    }

    /**
     * Deteremine if a value is of a transformable type.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function transformableType($value)
    {
        return is_object($value) || is_string($value);
    }

    /**
     * Get a registered transformer binding.
     *
     * @param  string|object  $class
     * @return \Dingo\Api\Transformer\Binding
     * @throws \RuntimeException
     */
    protected function getBinding($class)
    {
        if ($this->isCollection($class)) {
            return $this->getBindingFromCollection($class);
        } elseif ($this->boundByContract($class)) {
            return $this->createContractBinding($class);
        }

        $class = is_object($class) ? get_class($class) : $class;

        if (! $this->hasBinding($class)) {
            throw new RuntimeException('Unable to find bound transformer for "'.$class.'" class.');
        }

        return $this->bindings[$class];
    }

    /**
     * Create a new binding instance.
     *
     * @param  string|callable|object  $resolver
     * @param  array  $parameters
     * @param  \Closure  $callback
     * @return \Dingo\Api\Transformer\Binding
     */
    protected function createBinding($resolver, array $parameters = [], Closure $callback = null)
    {
        return new Binding($this->container ?: new Container, $resolver, $parameters, $callback);
    }

    /**
     * Create a new binding for an instance bound by a contract.
     *
     * @param  object  $instance
     * @return \Dingo\Api\Transformer\Binding
     */
    protected function createContractBinding($instance)
    {
        return $this->createBinding($instance->getTransformer());
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
        if ($this->isCollection($class)) {
            $class = $class->first();
        }

        $class = is_object($class) ? get_class($class) : $class;

        return isset($this->bindings[$class]);
    }

    /**
     * Determine if the instance is bound by the transformable contract.
     *
     * @param  string|object  $instance
     * @return bool
     */
    protected function boundByContract($instance)
    {
        if ($this->isCollection($instance)) {
            $instance = $instance->first();
        }

        return is_object($instance) and $instance instanceof TransformableInterface;
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
}

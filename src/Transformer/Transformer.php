<?php

namespace Dingo\Api\Transformer;

use RuntimeException;
use Illuminate\Http\Request;
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
     * Illuminate request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Array of registered transformer bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Set the illuminate request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
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
     * @return \Dingo\Api\Transformer\Factory
     */
    public function register($class, $resolver)
    {
        $this->bindings[$class] = $resolver;

        return $this;
    }

    /**
     * Alias for register.
     *
     * @param  string  $class
     * @param  string|callable|object  $resolver
     * @return \Dingo\Api\Transformer\Factory
     */
    public function registerBinding($class, $resolver)
    {
        return $this->register($class, $resolver);
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

        return $this->transformResponse($response, $this->resolveTransformerBinding($binding));
    }

    /**
     * Transform a response with a transformer.
     *
     * @param  string|object  $response
     * @param  object  $transformer
     * @return mixed
     */
    abstract public function transformResponse($response, $transformer);

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
     * Resolve a transfomer binding instance.
     *
     * @param  string|callable|object  $binding
     * @return mixed
     */
    protected function resolveTransformerBinding($binding)
    {
        if (is_string($binding)) {
            return $this->container->make($binding);
        } elseif (is_callable($binding)) {
            return call_user_func($binding, $this->container);
        }

        return $binding;
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
        if ($this->isCollection($class)) {
            return $this->getBindingFromCollection($class);
        }

        if ($this->boundByContract($class)) {
            return $class->getTransformer();
        }

        $class = is_object($class) ? get_class($class) : $class;

        if (! $this->hasBinding($class)) {
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
        if ($this->isCollection($class)) {
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
        if ($this->isCollection($class)) {
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
}

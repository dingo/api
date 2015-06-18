<?php

namespace Dingo\Api\Generator;

use ReflectionClass;
use Illuminate\Support\Collection;
use Dingo\Api\Generator\Annotation;
use phpDocumentor\Reflection\DocBlock;

class Resource extends Section
{
    /**
     * Resource identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Resource reflection instance.
     *
     * @var \ReflectionClass
     */
    protected $reflector;

    /**
     * Collection of annotations belonging to a resource.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $annotations;

    /**
     * Collection of actions belonging to a resource.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $actions;

    /**
     * Create a new resource instance.
     *
     * @param string                         $identifier
     * @param \ReflectionClass               $reflector
     * @param \Illuminate\Support\Collection $annotations
     * @param \Illuminate\Support\Collection $actions
     *
     * @return void
     */
    public function __construct($identifier, ReflectionClass $reflector, Collection $annotations, Collection $actions)
    {
        $this->identifier = $identifier;
        $this->reflector = $reflector;
        $this->annotations = $annotations;
        $this->actions = $actions;

        $this->setResourceOnActions();
    }

    /**
     * Set the resource on each of the actions.
     *
     * @return void
     */
    protected function setResourceOnActions()
    {
        $this->actions->each(function ($action) {
            $action->setResource($this);
        });
    }

    /**
     * Get the resource definition.
     *
     * @return string
     */
    public function getDefinition()
    {
        $definition = $this->getUri();

        if ($method = $this->getMethod()) {
            $definition = $method.' '.$definition;
        }

        return '# '.$this->getIdentifier().' ['.$definition.']';
    }

    /**
     * Get the actions belonging to the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * get the resource URI.
     *
     * @return string
     */
    public function getUri()
    {
        if (($annotation = $this->getAnnotationByType('Resource')) && isset($annotation->uri)) {
            return '/'.trim($annotation->uri, '/');
        }

        return '/';
    }

    /**
     * Get the resource method.
     *
     * @return string|null
     */
    public function getMethod()
    {
        if (($annotation = $this->getAnnotationByType('Resource')) && isset($annotation->method)) {
            return $annotation->method;
        }
    }

    /**
     * Get the resource description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return (new DocBlock($this->reflector))->getText();
    }

    /**
     * Get the resource identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        if (($annotation = $this->getAnnotationByType('Resource')) && isset($annotation->name)) {
            return $annotation->name;
        }

        return $this->identifier;
    }
}

<?php

namespace Dingo\Api\Generator;

use RuntimeException;
use ReflectionMethod;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\DocBlock;

class Action extends Section
{
    /**
     * Action reflector instance.
     *
     * @var \ReflectionMethod
     */
    protected $reflector;

    /**
     * Annotations belonging to the action.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $annotations;

    /**
     * Parent resource of the action.
     *
     * @var \Dingo\Api\Generator\Resource
     */
    protected $resource;

    /**
     * Create a new action instance.
     *
     * @param \ReflectionMethod              $reflector
     * @param \Illuminate\Support\Collection $annotations
     *
     * @return void
     */
    public function __construct(ReflectionMethod $reflector, Collection $annotations)
    {
        $this->reflector = $reflector;
        $this->annotations = $annotations;
    }

    /**
     * Get the actions definition.
     *
     * @return string
     */
    public function getDefinition()
    {
        $definition = $this->getMethod();

        if ($identifier = $this->getIdentifier()) {
            if ($uri = $this->getUri()) {
                $definition = $definition.' '.$uri;
            }

            $definition = $identifier.' ['.$definition.']';
        }

        return '## '.$definition;
    }

    /**
     * Get the actions version annotation.
     *
     * @return \Dingo\Api\Generator\Annotation\Versions|null
     */
    public function getVersions()
    {
        if ($annotation = $this->getAnnotationByType('Versions')) {
            return $annotation;
        }
    }

    /**
     * Get the actions response annotation.
     *
     * @return \Dingo\Api\Generator\Annotation\Response|null
     */
    public function getResponse()
    {
        if ($annotation = $this->getAnnotationByType('Response')) {
            return $annotation;
        }
    }

    /**
     * Get the actions request annotation.
     *
     * @return \Dingo\Api\Generator\Annotation\Request|null
     */
    public function getRequest()
    {
        if ($annotation = $this->getAnnotationByType('Request')) {
            return $annotation;
        }
    }

    /**
     * Get the actions transaction annotation.
     *
     * @return \Dingo\Api\Generator\Annotation\Transaction|null
     */
    public function getTransaction()
    {
        if ($annotation = $this->getAnnotationByType('Transaction')) {
            return $annotation;
        }
    }

    /**
     * Get the actions identifier.
     *
     * @return string|null
     */
    public function getIdentifier()
    {
        return (new DocBlock($this->reflector))->getShortDescription();
    }

    /**
     * Get the actions description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return (new DocBlock($this->reflector))->getLongDescription();
    }

    /**
     * Get the actions URI.
     *
     * @return string
     */
    public function getUri()
    {
        $uri = '/';

        if (($annotation = $this->getAnnotationByType('Method\Method')) && isset($annotation->uri)) {
            $uri = $annotation->uri;
        } else {
            return;
        }

        if (! starts_with($uri, '{')) {
            $uri = '/'.$uri;
        }

        return rtrim('/'.trim($this->resource->getUri(), '/').trim($uri, '/'), '/');
    }

    /**
     * Get the actions method.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getMethod()
    {
        if ($annotation = $this->getAnnotationByType('Method\Method')) {
            return strtoupper(substr(get_class($annotation), strrpos(get_class($annotation), '\\') + 1));
        }

        throw new RuntimeException('No HTTP method given, invalid API blueprint.');
    }

    /**
     * Set the parent resource on the action.
     *
     * @param \Dingo\Api\Generator\Resource $resource
     *
     * @return void
     */
    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }
}

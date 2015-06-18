<?php

namespace Dingo\Api\Generator\Annotation;

/**
 * @Annotation
 */
class Resource
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $method;
}

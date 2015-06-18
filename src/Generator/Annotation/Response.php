<?php

namespace Dingo\Api\Generator\Annotation;

/**
 * @Annotation
 */
class Response
{
    /**
     * @var int
     */
    public $statusCode;

    /**
     * @var string
     */
    public $contentType;

    /**
     * @var mixed
     */
    public $body;

    /**
     * @var array
     */
    public $headers = [];
}

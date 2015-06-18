<?php

namespace Dingo\Api\Generator\Annotation;

/**
 * @Annotation
 */
class Transaction
{
    /**
     * @array<Request|Response>
     */
    public $value;
}

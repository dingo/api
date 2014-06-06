<?php

namespace Dingo\Api\Transformer;

interface TransformableInterface
{
    /**
     * Get the transformer instance.
     *
     * @return mixed
     */
    public function getTransformer();
}

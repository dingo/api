<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Transformer\Transformer;

class TransformerStub extends Transformer
{
    public function transformResponse($response, $transformer, $binding)
    {
        if ($this->isCollection($response)) {
            return $response->transform(function ($response) use ($transformer) {
                return $transformer->transform($response);
            })->toArray();
        }

        return $transformer->transform($response);
    }
}

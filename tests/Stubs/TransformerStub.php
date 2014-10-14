<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Dingo\Api\Transformer\Binding;
use Dingo\Api\Transformer\TransformerInterface;

class TransformerStub implements TransformerInterface
{
    public function transform($response, $transformer, Binding $binding, Request $request)
    {
        if ($response instanceof Collection) {
            return $response->transform(function ($response) use ($transformer) {
                return $transformer->transform($response);
            })->toArray();
        }

        return $transformer->transform($response);
    }
}

<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Http\Request;
use Dingo\Api\Transformer\Binding;
use Illuminate\Support\Collection;
use Dingo\Api\Contract\Transformer\Adapter;

class TransformerStub implements Adapter
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

<?php

namespace Dingo\Api\Transformer;

use Illuminate\Http\Request;

interface TransformerInterface
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed                          $response
     * @param object                         $transformer
     * @param \Dingo\Api\Transformer\Binding $binding
     *
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request);
}

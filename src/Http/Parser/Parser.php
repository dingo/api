<?php

namespace Dingo\Api\Http\Parser;

use Illuminate\Http\Request;

interface Parser
{
    /**
     * Parse an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function parse(Request $request);
}

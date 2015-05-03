<?php

namespace Dingo\Api\Http\Parser;

use Dingo\Api\Http\Request;

interface ParserInterface
{
    /**
     * Parse an incoming request.
     *
     * @param  \Dingo\Api\Http\Request  $request
     * @return mixed
     */
    public function parse(Request $request);
}

<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

interface ValidatorInterface
{
    /**
     * Validate a request.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @return bool
     */
    public function validate(Request $request);
}

<?php

namespace Dingo\Api\Contract\Http\Validation;

use Illuminate\Http\Request;

interface Validator
{
    /**
     * Validate a request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validate(Request $request);
}

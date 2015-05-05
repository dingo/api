<?php

namespace Dingo\Api\Http\Validation;

use Illuminate\Http\Request;

interface ValidatorInterface
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

<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Contract\Http\Validator;
use Illuminate\Http\Request;

class HttpValidatorStub implements Validator
{
    public function validate(Request $request)
    {
        return $request->getMethod() === 'POST';
    }
}

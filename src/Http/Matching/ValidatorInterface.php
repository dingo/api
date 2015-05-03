<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

interface ValidatorInterface
{
    public function matches(Request $request);
}

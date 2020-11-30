<?php

namespace Dingo\Api\Http;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;

class FormRequest extends Request implements ValidatesWhenResolved
{
    use FormRequestTrait;
}

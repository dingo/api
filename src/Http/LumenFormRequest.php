<?php

namespace Dingo\Api\Http;

use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Laravel\Lumen\Http\Request as LumenRequest;

class LumenFormRequest extends LumenRequest implements ValidatesWhenResolved
{
    use FormRequestTrait;
}

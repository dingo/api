<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

class DomainValidator implements ValidatorInterface
{
    protected $domain;

    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    public function matches(Request $request)
    {
        return ! is_null($this->domain) && $request->header('host') == $this->domain;
    }
}

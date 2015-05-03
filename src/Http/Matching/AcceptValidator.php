<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

class AcceptValidator implements ValidatorInterface
{
    protected $vendor;

    protected $version;

    protected $format;

    public function __construct($vendor, $version, $format)
    {
        $this->vendor = $vendor;
        $this->version = $version;
        $this->format = $format;
    }

    public function matches(Request $request)
    {

    }
}

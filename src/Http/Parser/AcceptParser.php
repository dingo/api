<?php

namespace Dingo\Api\Http\Parser;

use Dingo\Api\Http\Request;

class AcceptParser implements ParserInterface
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

    public function parse(Request $request)
    {
        $default = 'application/vnd.'.$this->vendor.'.'.$this->version.'+'.$this->format;

        $pattern = '/application\/vnd\.([\w]+)\.(v[\d]+)\+([\w]+)/';

        if (! preg_match($pattern, $request->header('accept'), $matches)) {
            preg_match($pattern, $default, $matches);
        }

        return array_combine(['vendor', 'version', 'format'], array_slice($matches, 1));
    }
}

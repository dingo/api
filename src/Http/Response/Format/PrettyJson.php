<?php

namespace Dingo\Api\Http\Response\Format;

class PrettyJson extends Json
{
    protected function encode($content)
    {
        return json_encode($content,JSON_PRETTY_PRINT);
    }
}

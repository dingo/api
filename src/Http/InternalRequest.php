<?php

namespace Dingo\Api\Http;

class InternalRequest extends Request
{
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        // Pass parameters inside internal request into Laravel's JSON ParameterBag,
        // so that they can be accessed using $request->input()
        if ($this->isJson() && isset($this->request)) {
            $this->setJson($this->request);
        }
    }
}
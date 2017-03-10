<?php

namespace Dingo\Api\Http\Response\Format;

class Jsonp extends Json
{
    /**
     * Name of JSONP callback paramater.
     *
     * @var string
     */
    protected $callbackName = 'callback';

    /**
     * Create a new JSONP response formatter instance.
     *
     * @param string $callbackName
     *
     * @return void
     */
    public function __construct($callbackName = 'callback')
    {
        $this->callbackName = $callbackName;
    }

    /**
     * Determine if a callback is valid.
     *
     * @return bool
     */
    protected function hasValidCallback()
    {
        return $this->request->query->has($this->callbackName);
    }

    /**
     * Get the callback from the query string.
     *
     * @return string
     */
    protected function getCallback()
    {
        return $this->request->query->get($this->callbackName);
    }

    /**
     * Get the response content type.
     *
     * @return string
     */
    public function getContentType()
    {
        if ($this->hasValidCallback()) {
            return 'application/javascript';
        }

        return parent::getContentType();
    }

    /**
     * Encode the content to it's JSONP representation.
     *
     * @param array $content
     *
     * @return string
     */
    protected function encode(array $content)
    {
        $json_string = parent::encode($content);

        if ($this->hasValidCallback()) {
            return sprintf('%s(%s);', $this->getCallback(), $json_string);
        }

        return $json_string;
    }
}

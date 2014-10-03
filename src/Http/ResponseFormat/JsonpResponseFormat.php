<?php namespace Dingo\Api\Http\ResponseFormat;

class JsonpResponseFormat extends JsonResponseFormat
{
    /**
     * Name of JSONP callback paramater.
     *
     * @var string
     */
    protected $callbackName = 'callback';

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
     * Encode the content to its JSON representation.
     *
     * @param  string  $content
     * @return string
     */
    protected function encode($content)
    {
        if ($this->hasValidCallback()) {
            return sprintf('%s(%s);', $this->getCallback(), json_encode($content));
        }

        return parent::encode($content);
    }
}

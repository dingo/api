<?php

namespace Dingo\Api\Http\Response;

use Dingo\Api\Http\Response;
use Dingo\Api\Transformer\Binding;

class Builder
{
    /**
     * Transformer binding instance.
     *
     * @var \Dingo\Api\Transformer\Binding
     */
    protected $transformer;

    /**
     * The HTTP response headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The HTTP response status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Original resource object.
     * 
     * @var mixed
     */
    protected $original;

    /**
     * Create a new response builder instance.
     *
     * @param  mixed  $response
     * @param  \Dingo\Api\Transformer\Binding  $binding
     * @return void
     */
    public function __construct($response, Binding $binding = null)
    {
        $this->response = $response;
        $this->binding = $binding;
    }

    /**
     * Add a meta key and value pair.
     * 
     * @param  string  $key
     * @param  mixed  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addMeta($key, $value)
    {
        $this->binding->addMeta($key, $value);

        return $this;
    }

    /**
     * Add a meta key and value pair.
     * 
     * @param  string  $key
     * @param  mixed  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function meta($key, $value)
    {
        return $this->addMeta($key, $value);
    }

    /**
     * Set the meta data for the response.
     * 
     * @param  array  $meta
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setMeta(array $meta)
    {
        $this->binding->setMeta($meta);

        return $this;
    }

    /**
     * Add a header to the response
     *
     * @param  string  $name
     * @param  string  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add an array of headers.
     * 
     * @param  array  $headers
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Add a header to the response.
     * 
     * @param  string  $name
     * @param  string  $value
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function header($name, $value)
    {
        return $this->addHeader($name, $value);
    }

    /**
     * Add an array of headers.
     * 
     * @param  array  $headers
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function headers(array $headers)
    {
        return $this->addHeaders($headers);
    }

    /**
     * Set the responses status code.
     *
     * @param  int  $statusCode
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Build the response.
     *
     * @param  array|\League\Fractal\Resource\ResourceInterface  $data
     * @return \Illuminate\Http\Response
     */
    public function build()
    {
        return new Response($this->response, $this->statusCode, $this->headers);
    }
}

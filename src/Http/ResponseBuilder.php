<?php

namespace Dingo\Api\Http;

use Dingo\Api\Transformer\Binding;
use Symfony\Component\HttpFoundation\Cookie;

class ResponseBuilder
{
    /**
     * Transformer binding instance.
     *
     * @var \Dingo\Api\Transformer\Binding
     */
    protected $binding;

    /**
     * Response content.
     *
     * @var mixed
     */
    protected $response;

    /**
     * The HTTP response headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The HTTP response cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * The HTTP response status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Create a new response builder instance.
     *
     * @param mixed                          $response
     * @param \Dingo\Api\Transformer\Binding $binding
     *
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
     * @param string $key
     * @param mixed  $value
     *
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
     * @param string $key
     * @param mixed  $value
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function meta($key, $value)
    {
        return $this->addMeta($key, $value);
    }

    /**
     * Set the meta data for the response.
     *
     * @param array $meta
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setMeta(array $meta)
    {
        $this->binding->setMeta($meta);

        return $this;
    }

    /**
     * Add a cookie to the response.
     *
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function withCookie(Cookie $cookie)
    {
        $this->cookies[] = $cookie;

        return $this;
    }

    /**
     * Add a cookie to the response.
     *
     * @param \Symfony\Component\HttpFoundation\Cookie $cookie
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function cookie(Cookie $cookie)
    {
        return $this->withCookie($cookie);
    }

    /**
     * Add a header to the response.
     *
     * @param string $name
     * @param string $value
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add a header to the response.
     *
     * @param string $name
     * @param string $value
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function header($name, $value)
    {
        return $this->withHeader($name, $value);
    }

    /**
     * Set the responses status code.
     *
     * @param int $statusCode
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Set the response status code.
     *
     * @param int $statusCode
     *
     * @return \Dingo\Api\Http\ResponseBuilder
     */
    public function statusCode($statusCode)
    {
        return $this->setStatusCode($statusCode);
    }

    /**
     * Build the response.
     *
     * @return \Illuminate\Http\Response
     */
    public function build()
    {
        $response = new Response($this->response, $this->statusCode, $this->headers);

        foreach ($this->cookies as $cookie) {
            if ($cookie instanceof Cookie) {
                $response->withCookie($cookie);
            }
        }

        return $response;
    }
}

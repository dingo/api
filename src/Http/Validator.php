<?php

namespace Dingo\Api\Http;

use Illuminate\Http\Request as IlluminateRequest;

class Validator
{
    protected $domain;

    protected $prefix;

    /**
     * Create a new request validator instance.
     *
     * @param string $domain
     * @param string $prefix
     *
     * @return void
     */
    public function __construct($domain = null, $prefix = null)
    {
        $this->domain = $domain;
        $this->prefix = $prefix;
    }

    /**
     * Validate a request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validateRequest(IlluminateRequest $request)
    {
        return $this->validateDomain($request) || $this->validatePrefix($request);
    }

    /**
     * Validates domain in the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validateDomain(IlluminateRequest $request)
    {
        return ! is_null($this->domain) && $request->header('host') == $this->domain;
    }

    /**
     * Validates prefix in the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validatePrefix(IlluminateRequest $request)
    {
        $prefix = $this->filterAndExplode($this->prefix);

        $path = $this->filterAndExplode($request->getPathInfo());

        return ! is_null($this->prefix) && $prefix == array_slice($path, 0, count($prefix));
    }

    /**
     * Explode array on slash and remove empty values.
     *
     * @param array $array
     *
     * @return array
     */
    protected function filterAndExplode($array)
    {
        return array_filter(explode('/', $array));
    }
}

<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;

class DomainValidator implements ValidatorInterface
{
    /**
     * API domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Create a new domain validator instance.
     *
     * @param string $domain
     *
     * @return void
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Validate that the request domain matches the configured domain.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @return bool
     */
    public function validate(Request $request)
    {
        return ! is_null($this->domain) && $request->header('host') == $this->domain;
    }
}

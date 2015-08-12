<?php

namespace Dingo\Api\Http\Validation;

use Illuminate\Http\Request;
use Dingo\Api\Contract\Http\Validator;

class Domain implements Validator
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
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function validate(Request $request)
    {
        return ! is_null($this->domain) && $request->header('host') == $this->stripProtocol($this->domain);
    }

    /**
     * Strip the protocol from a domain.
     *
     * @param string $domain
     *
     * @return string
     */
    protected function stripProtocol($domain)
    {
        if (str_contains($domain, '://')) {
            $domain = substr($domain, strpos($domain, '://') + 3);
        }

        return $domain;
    }
}

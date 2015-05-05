<?php

namespace Dingo\Api\Http;

use Illuminate\Container\Container;
use Illuminate\Http\Request as IlluminateRequest;

class Validator
{
    /**
     * Container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Array of request validators.
     *
     * @var array
     */
    protected $validators = [
        'Dingo\Api\Http\Matching\DomainValidator',
        'Dingo\Api\Http\Matching\PrefixValidator',
        'Dingo\Api\Http\Matching\AcceptValidator'
    ];

    /**
     * Create a new request validator instance.
     *
     * @param \Illuminate\Container\Container $container
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
        foreach ($this->validators as $validator) {
            $validator = $this->container->make($validator);

            if (! $validator->matches($request)) {
                return false;
            }
        }

        return true;
    }
}

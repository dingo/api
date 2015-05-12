<?php

namespace Dingo\Api\Http;

use Illuminate\Container\Container;
use Illuminate\Http\Request as IlluminateRequest;
use Dingo\Api\Http\Validation\Validator as ValidatorInterface;

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
        'Dingo\Api\Http\Validation\Domain',
        'Dingo\Api\Http\Validation\Prefix',
        'Dingo\Api\Http\Validation\Accept'
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
        $status = false;

        foreach ($this->validators as $validator) {
            $validator = $this->container->make($validator);

            if ($validator instanceof ValidatorInterface && $validator->validate($request)) {
                $status = true;
            }
        }

        return $status;
    }
}

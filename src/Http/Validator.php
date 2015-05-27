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
     * Replace the validators.
     *
     * @param array $validators
     *
     * @return void
     */
    public function replace(array $validators)
    {
        $this->validators = $validators;
    }

    /**
     * Merge an array of validators.
     *
     * @param array $validators
     *
     * @return void
     */
    public function merge(array $validators)
    {
        $this->validators = array_merge($this->validators, $validators);
    }

    /**
     * Extend the validators.
     *
     * @param string|\Dingo\Api\Http\Validator
     *
     * @return void
     */
    public function extend($validator)
    {
        $this->validators[] = $validator;
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

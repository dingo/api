<?php

namespace Dingo\Api\Http\RateLimit;

abstract class Throttle implements ThrottleInterface
{
    /**
     * Array of throttle options.
     *
     * @var array
     */
    protected $options = ['limit' => 60, 'expires' => 60];

    /**
     * Create a new throttle instance.
     *
     * @param array $options
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get the throttles options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get the throttles request limit.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->options['limit'];
    }

    /**
     * Get the time in minutes that the throttles request limit will expire.
     *
     * @return int
     */
    public function getExpires()
    {
        return $this->options['expires'];
    }
}

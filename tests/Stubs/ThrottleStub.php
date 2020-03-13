<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Http\RateLimit\Throttle\Throttle;
use Illuminate\Container\Container;

class ThrottleStub extends Throttle
{
    protected $enabled;

    public function __construct(array $options = ['limit' => 60, 'expires' => 60], $enabled = true)
    {
        $this->enabled = $enabled;

        parent::__construct($options);
    }

    public function match(Container $app)
    {
        return $this->enabled;
    }
}

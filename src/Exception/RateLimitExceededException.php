<?php

namespace Dingo\Api\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RateLimitExceededException extends HttpException
{
    /**
     * Create a new rate limit exceeded exception instance.
     *
     * @param string     $message
     * @param \Exception $previous
     * @param array      $headers
     * @param int        $code
     *
     * @return void
     */
    public function __construct($message = null, Exception $previous = null, $headers = [], $code = 0)
    {
        parent::__construct(429, $message ?: 'You have exceeded your rate limit.', $previous, $headers, $code);
    }
}

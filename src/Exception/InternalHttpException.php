<?php

namespace Dingo\Api\Exception;

use Exception;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalHttpException extends HttpException
{
    /**
     * Create a new internal HTTP exception instance.
     *
     * @param \Illuminate\Http\Response $response
     * @param string                    $message
     * @param \Exception                $previous
     * @param array                     $headers
     * @param int                       $code
     *
     * @return void
     */
    public function __construct(Response $response, $message = null, Exception $previous = null, array $headers = [], $code = 0)
    {
        $this->response = $response;

        parent::__construct($response->getStatusCode(), $message, $previous, $headers, $code);
    }

    /**
     * Get the response of the internal request.
     *
     * @return \Illuminate\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}

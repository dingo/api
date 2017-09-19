<?php

namespace Dingo\Api\Contract\Debug;

use Exception;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param \Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Exception $exception);
}

<?php

namespace Dingo\Api\Contract\Exception;

use Exception;

interface Handler
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

<?php

namespace Dingo\Api\Contract\Debug;

use Throwable;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param \Throwable $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Throwable $exception);
}

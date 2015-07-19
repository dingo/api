<?php

namespace Dingo\Api\Http\Validation;

use Illuminate\Http\Request;
use Dingo\Api\Contract\Http\Validation\Validator;
use Dingo\Api\Http\Parser\Accept as AcceptParser;

class Accept implements Validator
{
    /**
     * Accept parser instance.
     *
     * @var \Dingo\Api\Http\Parser\Accept
     */
    protected $accept;

    /**
     * Indicates if the accept matching is strict.
     *
     * @var bool
     */
    protected $strict;

    /**
     * Create a new accept validator instance.
     *
     * @param \Dingo\Api\Http\Parser\Accept $accept
     * @param bool                          $strict
     *
     * @return void
     */
    public function __construct(AcceptParser $accept, $strict = false)
    {
        $this->accept = $accept;
        $this->strict = $strict;
    }

    /**
     * Validate the accept header on the request. If this fails it will throw
     * an HTTP exception that will be caught by the middleware. This
     * validator should always be run last and must not return
     * a success boolean.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return void
     */
    public function validate(Request $request)
    {
        $this->accept->parse($request, $this->strict);
    }
}

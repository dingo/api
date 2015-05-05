<?php

namespace Dingo\Api\Http\Matching;

use Dingo\Api\Http\Request;
use Dingo\Api\Http\Parser\AcceptParser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcceptValidator implements ValidatorInterface
{
    /**
     * Accept parser instance.
     *
     * @var \Dingo\Api\Http\Parser\AcceptParser
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
     * @param \Dingo\Api\Http\Parser\AcceptParser $accept
     * @param bool                                $strict
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
     * validator should always be run last.
     *
     * @param \Dingo\Api\Http\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return bool
     */
    public function validate(Request $request)
    {
        $this->accept->parse($request, $strict);

        return true;
    }
}

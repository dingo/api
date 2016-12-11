<?php

namespace Dingo\Api\Http\Validation;

use Illuminate\Http\Request;
use Dingo\Api\Contract\Http\Validator;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * Indicates if the accept matching is strict Spec Url.
     *
     * @var null
     */
    protected $strictSpecUrl;

    /**
     * Create a new accept validator instance.
     *
     * @param \Dingo\Api\Http\Parser\Accept $accept
     * @param bool                          $strict
     * @param null                          $strictSpecUrl
     *
     * @return void
     */
    public function __construct(AcceptParser $accept, $strict = false, $strictSpecUrl = null)
    {
        $this->accept = $accept;
        $this->strict = $strict;
        $this->strictSpecUrl = $strictSpecUrl;
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
     * @return bool
     */
    public function validate(Request $request)
    {
        try {
            $this->accept->parse($request, $this->strict, $this->strictSpecUrl);
        } catch (BadRequestHttpException $exception) {
            if ($request->getMethod() === 'OPTIONS') {
                return true;
            }

            throw $exception;
        }
    }
}

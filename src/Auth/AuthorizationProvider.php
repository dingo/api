<?php

namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AuthorizationProvider extends Provider
{
    /**
     * Array of provider specific options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Validate the requests authorization header for the provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function validateAuthorizationHeader(Request $request)
    {
        if (! starts_with(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod())) {
            throw new BadRequestHttpException;
        }
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    abstract public function getAuthorizationMethod();
}

<?php

namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AuthorizationProvider implements ProviderInterface
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
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return bool
     */
    public function validateAuthorizationHeader(Request $request)
    {
        if (Str::startsWith(strtolower($request->headers->get('authorization')), $this->getAuthorizationMethod())) {
            return true;
        }

        throw new BadRequestHttpException();
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    abstract public function getAuthorizationMethod();
}

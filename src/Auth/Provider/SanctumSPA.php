<?php

namespace Dingo\Api\Auth\Provider;

use Dingo\Api\Contract\Auth\Provider;
use Dingo\Api\Routing\Route;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SanctumSPA implements Provider
{
    /**
     * Illuminate authentication manager.
     *
     * @var \Illuminate\Auth\AuthManager
     */
    private $auth;

    /**
     * Create a new basic provider instance.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     *
     * @return void
     */
    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticate request with Basic.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        if ($user = $this->auth->guard('web')->user()) {
            return $user;
        }
        throw new UnauthorizedHttpException('',
            'Unauthenticated'
        );
    }
}

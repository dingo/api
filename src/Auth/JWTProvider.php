<?php

namespace Dingo\Api\Auth;

use Exception;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Illuminate\Auth\AuthManager;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class JWTProvider extends AuthorizationProvider
{
    /**
     * The JWTAuth instance
     *
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * Illuminate application authorization manager.
     *
     * @var \Illuminate\Auth\AuthManager
     */
    protected $appAuth;

    /**
     * Create a new JWT provider instance.
     *
     * @param  \Tymon\JWTAuth\JWTAuth       $auth
     * @param  \Illuminate\Auth\AuthManager $appAuth
     * @return void
     */
    public function __construct(JWTAuth $auth, AuthManager $appAuth)
    {
        $this->auth    = $auth;
        $this->appAuth = $appAuth;
    }

    /**
     * Authenticate request with a JWT
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Dingo\Api\Routing\Route  $route
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        $token = $this->getToken($request);

        try {
            $this->auth->login($token);

            return $this->appAuth->user();
        } catch (JWTException $e) {
            throw new UnauthorizedHttpException('JWTAuth', $e->getMessage());
        }
    }

    /**
     * Get the JWT from the request
     *
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    protected function getToken(Request $request)
    {
        try {
            $this->validateAuthorizationHeader($request);

            $token = $this->parseAuthorizationHeader($request);
        } catch (Exception $exception) {
            if (! $token = $request->query('token', false)) {
                throw $exception;
            }
        }

        return $token;
    }

    /**
     * Parse JWT from the authorization header
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function parseAuthorizationHeader(Request $request)
    {
        return trim(str_ireplace($this->getAuthorizationMethod(), '', $request->header('authorization')));
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    public function getAuthorizationMethod()
    {
        return 'bearer';
    }
}

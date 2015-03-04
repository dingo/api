<?php

namespace Dingo\Api\Auth;

use Dingo\Api\Routing\Route;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class JWTProvider extends AuthorizationProvider
{
    /**
     * The JWTAuth instance.
     *
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * Create a new JWT provider instance.
     *
     * @param \Tymon\JWTAuth\JWTAuth $auth
     *
     * @return void
     */
    public function __construct(JWTAuth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Authenticate request with a JWT.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        $token = $this->getToken($request);

        try {
            return $this->auth->login($token);
        } catch (JWTException $e) {
            throw new UnauthorizedHttpException('JWTAuth', $e->getMessage());
        }
    }

    /**
     * Get the JWT from the request.
     *
     * @param \Illuminate\Http\Request $request
     *
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
     * Parse JWT from the authorization header.
     *
     * @param \Illuminate\Http\Request $request
     *
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

<?php

namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicProvider extends AuthorizationProvider
{
    /**
     * Illuminate authentication manager.
     *
     * @var \Illuminate\Auth\AuthManager
     */
    protected $auth;

    /**
     * Basic auth identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Create a new Dingo\Api\Auth\BasicProvider instance.
     *
     * @param  \Illuminate\Auth\AuthManager  $auth
     * @param  string  $identifier
     * @return void
     */
    public function __construct(AuthManager $auth, $identifier = 'email')
    {
        $this->auth = $auth;
        $this->identifier = $identifier;
    }

    /**
     * Authenticate request with Basic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        $this->validateAuthorizationHeader($request);

        if ($response = $this->auth->onceBasic($this->identifier) and $response->getStatusCode() === 401) {
            throw new UnauthorizedHttpException('Basic', 'Invalid authentication credentials.');
        }

        return $this->auth->user();
    }

    /**
     * Get the providers authorization method.
     *
     * @return string
     */
    public function getAuthorizationMethod()
    {
        return 'basic';
    }
}

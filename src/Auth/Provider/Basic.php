<?php

namespace Dingo\Api\Auth\Provider;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Basic extends Authorization
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
     * Extra conditions.
     *
     * @var array
     */
    private $extraConditions;

    /**
     * Create a new basic provider instance.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     * @param string                       $identifier
     * @param array                        $extraConditions
     *
     * @return void
     */
    public function __construct(AuthManager $auth, $identifier = 'email', $extraConditions = [])
    {
        $this->auth = $auth;
        $this->identifier = $identifier;
        $this->extraConditions = $extraConditions;
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
        $this->validateAuthorizationHeader($request);

        if (($response = $this->auth->onceBasic($this->identifier, $this->extraConditions)) && $response->getStatusCode() === 401) {
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

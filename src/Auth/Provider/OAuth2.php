<?php

namespace Dingo\Api\Auth\Provider;

use Exception;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Exception\OAuthException;
use League\OAuth2\Server\Exception\InvalidScopeException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuth2 extends Authorization
{
    /**
     * OAuth 2.0 resource server instance.
     *
     * @var \League\OAuth2\Server\Resource
     */
    protected $resource;

    /**
     * Indicates whether access token is limited to headers only.
     *
     * @var bool
     */
    protected $httpHeadersOnly = false;

    /**
     * User resolver.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * Client resolver.
     *
     * @var callable
     */
    protected $clientResolver;

    /**
     * Create a new OAuth 2.0 provider instance.
     *
     * @param \League\OAuth2\Server\ResourceServer $resource
     * @param bool                                 $httpHeadersOnly
     *
     * @return void
     */
    public function __construct(ResourceServer $resource, $httpHeadersOnly = false)
    {
        $this->resource = $resource;
        $this->httpHeadersOnly = $httpHeadersOnly;
    }

    /**
     * Authenticate request with the OAuth 2.0 resource server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     * @throws \Exception
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        try {
            $this->validateAuthorizationHeader($request);
        } catch (Exception $exception) {
            if (! $request->query('access_token', false)) {
                throw $exception;
            }
        }

        try {
            $this->resource->isValidRequest($this->httpHeadersOnly);

            $token = $this->resource->getAccessToken();

            if ($route->scopeStrict()) {
                $this->validateAllRouteScopes($token, $route);
            } else {
                $this->validateAnyRouteScopes($token, $route);
            }

            return $this->resolveResourceOwner($token);
        } catch (OAuthException $exception) {
            throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
        }
    }

    /**
     * Resolve the resource owner.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token
     *
     * @return mixed
     */
    protected function resolveResourceOwner(AccessTokenEntity $token)
    {
        $session = $token->getSession();

        if ($session->getOwnerType() == 'client') {
            return call_user_func($this->clientResolver, $session->getOwnerId());
        }

        return call_user_func($this->userResolver, $session->getOwnerId());
    }

    /**
     * Validate a route has any scopes.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token
     * @param \Dingo\Api\Routing\Route                       $route
     *
     * @throws \League\OAuth2\Server\Exception\InvalidScopeException
     *
     * @return bool
     */
    protected function validateAnyRouteScopes(AccessTokenEntity $token, Route $route)
    {
        $scopes = $route->scopes();

        if (empty($scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($token->hasScope($scope)) {
                return true;
            }
        }

        throw new InvalidScopeException($scope);
    }

    /**
     * Validate a route has all scopes.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token
     * @param \Dingo\Api\Routing\Route                       $route
     *
     * @throws \League\OAuth2\Server\Exception\InvalidScopeException
     *
     * @return bool
     */
    protected function validateAllRouteScopes(AccessTokenEntity $token, Route $route)
    {
        $scopes = $route->scopes();

        foreach ($scopes as $scope) {
            if (! $token->hasScope($scope)) {
                throw new InvalidScopeException($scope);
            }
        }

        return true;
    }

    /**
     * Set the resolver to fetch a user.
     *
     * @param callable $resolver
     *
     * @return \Dingo\Api\Contract\Auth\Provider
     */
    public function setUserResolver(callable $resolver)
    {
        $this->userResolver = $resolver;

        return $this;
    }

    /**
     * Set the resolver to fetch a client.
     *
     * @param callable $resolver
     *
     * @return \Dingo\Api\Contract\Auth\Provider
     */
    public function setClientResolver(callable $resolver)
    {
        $this->clientResolver = $resolver;

        return $this;
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

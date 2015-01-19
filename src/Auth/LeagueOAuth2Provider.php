<?php

namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Exception\OAuthException;
use League\OAuth2\Server\Exception\InvalidScopeException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LeagueOAuth2Provider extends AuthorizationProvider
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
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Routing\Route $route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
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

            $this->validateRouteScopes($token = $this->resource->getAccessToken(), $route);

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
     * Validate a routes scopes.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token
     * @param \Dingo\Api\Routing\Route                       $route
     *
     * @throws \League\OAuth2\Server\Exception\InvalidScopeException
     *
     * @return bool
     */
    protected function validateRouteScopes(AccessTokenEntity $token, Route $route)
    {
        $scopes = $route->scopes();

        if (empty($scopes)) {
            return true;
        }


        if (is_string($scopes)) {

            $scopes = explode(',', $scopes);

            $scopes = array_map(function($scope) {

                return trim($scope);

            }, $scopes);
        }

        foreach ($scopes as $scope) {
            if ($token->hasScope($scope)) {
                return true;
            }
        }

        throw new InvalidScopeException($scope);
    }

    /**
     * Set the resolver to fetch a user.
     *
     * @param callable $resolver
     *
     * @return \Dingo\Api\Auth\LeagueOAuth2Provider
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
     * @return \Dingo\Api\Auth\LeagueOAuth2Provider
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

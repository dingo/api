<?php

namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use League\OAuth2\Server\Resource;
use League\OAuth2\Server\Exception\InvalidAccessTokenException;
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
     * @param  \League\OAuth2\Server\Resource  $resource
     * @param  bool  $httpHeadersOnly
     * @return void
     */
    public function __construct(Resource $resource, $httpHeadersOnly = false)
    {
        $this->resource = $resource;
        $this->httpHeadersOnly = $httpHeadersOnly;
    }

    /**
     * Authenticate request with the OAuth 2.0 resource server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
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
            $this->validateRouteScopes($route);

            $this->resource->isValid($this->httpHeadersOnly);

            return $this->resolveResourceOwner();
        } catch (InvalidAccessTokenException $exception) {
            throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
        }
    }

    /**
     * Resolve the resource owner.
     *
     * @return mixed
     */
    protected function resolveResourceOwner()
    {
        if ($this->resource->getOwnerType() == 'client') {
            return call_user_func($this->clientResolver, $this->resource->getOwnerId());
        }

        return call_user_func($this->userResolver, $this->resource->getOwnerId());
    }

    /**
     * Validate a routes scopes.
     *
     * @return bool
     * @throws \League\OAuth2\Server\Exception\InvalidAccessTokenException
     */
    protected function validateRouteScopes(Route $route)
    {
        $scopes = $route->scopes();

        if (empty($scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($this->resource->hasScope($scope)) {
                return true;
            }
        }

        throw new InvalidAccessTokenException('Access token is not associated with any of the requested scopes.');
    }

    /**
     * Set the resolver to fetch a user.
     *
     * @param  callable  $resolver
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
     * @param  callable  $resolver
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

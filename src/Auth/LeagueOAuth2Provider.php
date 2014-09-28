<?php

namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use League\OAuth2\Server\Resource;
use League\OAuth2\Server\Exception\InvalidAccessTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LeagueOAuth2Provider extends AuthorizationProvider
{
    /**
     * OAuth 2.0 resource server instance.
     *
     * @var \Dingo\OAuth2\Server\Resource
     */
    protected $resource;

    /**
     * Indicates whether access token is limited to headers only.
     *
     * @var bool
     */
    protected $httpHeadersOnly = false;

    /**
     * Callback to fetch a user.
     *
     * @var callable
     */
    protected $userCallback;

    /**
     * Callback to fetch a client.
     *
     * @var callable
     */
    protected $clientCallback;

    /**
     * Create a new Dingo\Api\Auth\OAuth2Provider instance.
     *
     * @param  \Dingo\OAuth2\Server\Resource  $resource
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
     */
    public function authenticate(Request $request, Route $route)
    {
        try {
            $this->validateAuthorizationHeader($request);
        } catch (Exception $exception) {
            // If we catch an exception here it means the header was missing so we'll
            // now look for the access token in the query string. If we don't have
            // the query string either then we'll re-throw the exception.
            if (! $request->query('access_token', false)) {
                throw $exception;
            }
        }

        try {
            $this->resource->isValid($this->httpHeadersOnly);

            foreach ($this->getRouteScopes($route) as $scope) {
                if (! $this->resource->hasScope($scope)) {
                    throw new InvalidAccessTokenException('Requested scope "'.$scope.'" is not associated with this access token.');
                }
            }

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
            return call_user_func($this->clientCallback, $this->resource->getOwnerId());
        }

        return call_user_func($this->userCallback, $this->resource->getOwnerId());
    }

    /**
     * Get the routes scopes.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteScopes(Route $route)
    {
        $action = $route->getAction();

        return (array) array_get($action, 'scopes', []);
    }

    /**
     * Set the callback to fetch a user.
     *
     * @param  callable  $callback
     * @return \Dingo\Api\Auth\LeagueOAuth2Provider
     */
    public function setUserCallback(callable $callback)
    {
        $this->userCallback = $callback;

        return $this;
    }

    /**
     * Set the callback to fetch a client.
     *
     * @param  callable  $callback
     * @return \Dingo\Api\Auth\LeagueOAuth2Provider
     */
    public function setClientCallback(callable $callback)
    {
        $this->clientCallback = $callback;

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

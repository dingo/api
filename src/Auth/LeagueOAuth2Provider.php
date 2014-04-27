<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use League\OAuth2\Server\Resource;
use League\OAuth2\Server\Exception\InvalidAccessTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class LeagueOAuth2Provider extends AuthorizationProvider {

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
	 * @return int
	 */
	public function authenticate(Request $request, Route $route)
	{
		try
		{
			$this->validateAuthorizationHeader($request);
		}
		catch (Exception $exception)
		{
			// If we catch an exception here it means the header was missing so we'll
			// now look for the access token in the query string. If we don't have
			// the query string either then we'll re-throw the exception.
			if ( ! $request->query('access_token', false))
			{
				throw $exception;
			}
		}

		try
		{
			$this->resource->isValid($this->httpHeadersOnly);

			foreach ($this->getRouteScopes($route) as $scope)
			{
				if ( ! $this->resource->hasScope($scope))
				{
					throw new InvalidAccessTokenException('Requested scope "'.$scope.'" is not associated with this access token.');
				}
			}

			return $this->resource->getOwnerId();
		}
		catch (InvalidAccessTokenException $exception)
		{
			throw new UnauthorizedHttpException('Bearer', $exception->getMessage(), $exception);
		}
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

		return isset($action['scopes']) ? (array) $action['scopes'] : [];
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

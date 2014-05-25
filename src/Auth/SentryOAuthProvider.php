<?php
/**
 * Created by PhpStorm.
 * User: kingpin
 * Date: 5/20/14
 * Time: 6:40 AM
 */

namespace Dingo\Api\Auth;

use Cartalyst\Sentry\Sentry;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use League\OAuth2\Server\Resource;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class SentryOAuthProvider extends LeagueOAuth2Provider
{
	/**
	 * @param Resource $resource
	 * @param Sentry   $sentry
	 * @param bool     $httpHeadersOnly
	 */
	public function __construct(Resource $resource, Sentry $sentry, $httpHeadersOnly = false)
	{
		$this->resource = $resource;
		$this->httpHeadersOnly = $httpHeadersOnly;
		$this->sentry = $sentry;
	}

	/**
	 * Authenticate request with the OAuth 2.0 resource server.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\Route $route
	 * @return int
	 */
	public function authenticate(Request $request, Route $route)
	{
		$id = parent::authenticate($request, $route);
		$user = $this->sentry->getUserRepository()->findById($id);
		$this->sentry->stateless($user);
		return $id;
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

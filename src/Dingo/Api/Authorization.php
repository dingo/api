<?php namespace Dingo\Api;

use Dingo\Api\Http\Response;
use League\Oauth2\Server\Exception\ClientException;
use League\OAuth2\Server\Authorization as AuthorizationServer;

class Authorization {

	/**
	 * Authorization server instance.
	 * 
	 * @var \League\OAuth2\Server\Authorization
	 */
	protected $authServer;

	/**
	 * Array of exception status codes.
	 * 
	 * @var array
	 */
	protected $exceptionStatusCodes = [
        'invalid_request'           =>  400,
        'unauthorized_client'       =>  400,
        'access_denied'             =>  401,
        'unsupported_response_type' =>  400,
        'invalid_scope'             =>  400,
        'server_error'              =>  500,
        'temporarily_unavailable'   =>  400,
        'unsupported_grant_type'    =>  501,
        'invalid_client'            =>  401,
        'invalid_grant'             =>  400,
        'invalid_credentials'       =>  400,
        'invalid_refresh'           =>  400,
    ];

    /**
     * Create a new Dingo\Api\Authorization instance.
     * 
     * @param  \Leage\OAuth2\Server\Authorization  $authServer
     * @return void
     */
	public function __construct(AuthorizationServer $authServer)
	{
		$this->authServer = $authServer;
	}

	/**
	 * Attempt to issue an access token.
	 * 
	 * @param  array  $payload
	 * @return \Dingo\Api\Http\Response
	 */
	public function token(array $payload)
	{
		try
		{
			return new Response($this->authServer->issueAccessToken($payload));
		}
		catch (ClientException $exception)
		{
			$statusCode = $this->exceptionStatusCodes[$this->authServer->getExceptionType($exception->getCode())];

			return new Response($exception->getMessage(), $statusCode);
		}
	}

	/**
	 * Get the authorization server instance.
	 * 
	 * @return \League\OAuth2\Server\Authorization
	 */
	public function getAuthServer()
	{
		return $this->authServer;
	}

}
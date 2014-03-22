<?php namespace Dingo\Api;

use Dingo\Api\Http\Response;
use League\Oauth2\Server\Exception\ClientException;

class Authorization {

	/**
	 * Authorization server instance.
	 * 
	 * @var \League\OAuth2\Server\Authorization
	 */
	protected $server;

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
	 * @param  \Leage\OAuth2\Server\Authorization  $server
	 * @return void
	*/
	public function __construct(\League\OAuth2\Server\Authorization $server)
	{
		$this->server = $server;
	}

	/**
	 * Attempt to issue an access token.
	 * 
	 * @param  array  $payload
	 * @return \Dingo\Api\Http\Response
	 */
	public function token(array $payload)
	{
		return $this->server->issueAccessToken($payload);
		try
		{
			return new Response($this->server->issueAccessToken($payload));
		}
		catch (ClientException $exception)
		{
			$statusCode = $this->exceptionStatusCodes[$this->server->getExceptionType($exception->getCode())];

			return new Response($exception->getMessage(), $statusCode);
		}
	}

}
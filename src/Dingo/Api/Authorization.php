<?php namespace Dingo\Api;

use Dingo\Api\Http\Response;
use Dingo\OAuth2\Exception\ClientException;

class Authorization {

	/**
	 * Authorization server instance.
	 * 
	 * @var \Dingo\OAuth2\Server\Authorization
	 */
	protected $server;

	/**
	 * Create a new Dingo\Api\Authorization instance.
	 * 
	 * @param  \Dingo\OAuth2\Server\Authorization  $server
	 * @return void
	*/
	public function __construct(\Dingo\OAuth2\Server\Authorization $server)
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
		try
		{
			return $this->server->issueToken($payload);
		}
		catch (ClientException $exception)
		{
			return new Response($exception->getMessage(), $exception->getStatusCode());
		}
	}

}
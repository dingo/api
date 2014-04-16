<?php namespace Dingo\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicProvider extends AuthorizationProvider {

	/**
	 * Illuminate authentication manager.
	 * 
	 * @var \Illuminate\Auth\AuthManager
	 */
	protected $auth;

	/**
	 * Array of provider speicifc options.
	 * 
	 * @var array
	 */
	protected $options = ['identifier' => 'email'];

	/**
	 * Create a new Dingo\Api\Auth\BasicProvider instance.
	 * 
	 * @param  \Illuminate\Auth\AuthManager  $auth
	 * @param  array  $options
	 * @return void
	 */
	public function __construct(AuthManager $auth, array $options)
	{
		$this->auth = $auth;
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * Authenticate request with Basic.
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 * @return int
	 */
	public function authenticate(Request $request)
	{
		$this->validateAuthorizationHeader($request);

		if ($response = $this->auth->onceBasic($this->options['identifier']) and $response->getStatusCode() === 401)
		{
			throw new UnauthorizedHttpException('Basic', 'Invalid authentication credentials.');
		}

		return $this->auth->user()->id;
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
<?php namespace Dingo\Api\Auth;

use Exception;
use Illuminate\Http\Request;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Router;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthManager;
use Dingo\Api\Http\InternalRequest;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Shield {

	/**
	 * Illuminate auth instance.
	 * 
	 * @var \Illuminate\Auth\AuthManager
	 */
	protected $auth;

	/**
	 * Array of authentication providers.
	 * 
	 * @var array
	 */
	protected $providers;

	/**
	 * Authenticated user ID.
	 * 
	 * @var int
	 */
	protected $userId;

	/**
	 * Authenticated user instance.
	 * 
	 * @var \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	protected $user;

    /**
     * Create a new Dingo\Api\Authentication instance.
     * 
     * @param  \Illuminate\Auth\AuthManager  $auth
     * @param  array  $providers
     * @return void
     */
	public function __construct(AuthManager $auth, array $providers)
	{
		$this->auth = $auth;
		$this->providers = $providers;
	}

	/**
	 * Authenticate the current request.
	 * 
	 * @return null|\Dingo\Api\Http\Response
	 */
	public function authenticate(Request $request, Route $route)
	{
		$exceptionStack = [];

		// Spin through each of the registered authentication providers and attempt to
		// authenticate through one of them. This allows a developer to implement
		// and allow a number of different authentication mechanisms.
		foreach ($this->providers as $provider)
		{
			try
			{
				return $this->userId = $provider->authenticate($request, $route);
			}
			catch (UnauthorizedHttpException $exception)
			{
				$exceptionStack[] = $exception;
			}
			catch (Exception $exception)
			{
				// We won't add this exception to the stack as it's thrown when the provider
				// is unable to authenticate due to the correct authorization header not
				// being set. We will throw an exception for this below.
			}
		}

		$exception = array_shift($exceptionStack);

		if ($exception === null)
		{
			$exception = new UnauthorizedHttpException(null, 'Failed to authenticate because of bad credentials or an invalid authorization header.');
		}

		throw $exception;
	}

	/**
	 * Get the authenticated user.
	 * 
	 * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	public function getUser()
	{
		if ($this->user) return $this->user;

		if ( ! $this->userId) return null;

		if ( ! $this->auth->check())
		{
			$this->auth->onceUsingId($this->userId);
		}

		return $this->user = $this->auth->user();
	}

	/**
	 * Alias for getUser.
	 * 
	 * @return \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model
	 */
	public function user()
	{
		return $this->getUser();
	}

	/**
	 * Set the authenticated user.
	 * 
	 * @param  \Illuminate\Auth\GenericUser|\Illuminate\Database\Eloquent\Model  $user
	 * @return \Dingo\Api\Authentication
	 */
	public function setUser($user)
	{
		$this->user = $user;

		return $this;
	}

}

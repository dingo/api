<?php namespace Dingo\Api\Auth;

use Exception;
use BadMethodCallException;
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
	 * The provider used for authentication.
	 * 
	 * @var \Dingo\Api\Auth\Provider
	 */
	protected $usedProvider;

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
				$id = $provider->authenticate($request, $route);

				$this->usedProvider = $provider;

				return $this->userId = $id;
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
		if ($this->user)
		{
			return $this->user;
		}
		elseif ( ! $this->userId)
		{
			return null;
		}
		elseif ( ! $this->auth->check())
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

	/**
	 * Check if a user has authenticated with the API.
	 * 
	 * @return bool
	 */
	public function check()
	{
		return ! is_null($this->user());
	}

	/**
	 * Get the provider used for authentication.
	 * 
	 * @return \Dingo\Api\Auth\Provider
	 */
	public function getUsedProvider()
	{
		return $this->usedProvider;
	}

	/**
	 * Determine if the provider used was an OAuth 2.0 provider.
	 * 
	 * @return bool
	 */
	public function wasOAuth()
	{
		return $this->getUsedProvider() instanceof OAuth2ProviderInterface;
	}

	/**
	 * Magically call methods on the authenticated provider used.
	 * 
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		$provider = $this->getUsedProvider();

		if (method_exists($provider, $method))
		{
			return call_user_func_array([$provider, $method], $parameters);
		}

		throw new BadMethodCallException('Method "'.$method.'" not found.');
	}

}

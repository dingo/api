<?php namespace Dingo\Api;

use Exception;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException {

	/**
	 * Errors message bag.
	 * 
	 * @var \Illuminate\Support\MessageBar
	 */
	protected $errors;

	/**
	 * Create a new API HTTP exception instance.
	 * 
	 * @param  int  $statusCode
	 * @param  \Illuminate\Support\MessageBag  $errors
	 * @param  string  $message
	 * @param  \Exception  $previous
	 * @param  array  $headers
	 * @param  int  $code
	 * @return void
	 */
	public function __construct($statusCode, $message = null, $errors = null, Exception $previous = null, array $headers = array(), $code = 0)
    {
    	parent::__construct($statusCode, $message, $previous, $headers, $code);

    	$this->errors = $errors ?: new MessageBag;
    }

	/**
	 * Determine if response status code is a client error.
	 * 
	 * @return bool
	 */
	public function isClientError()
	{
		return starts_with($this->getStatusCode(), 4);
	}

	/**
	 * Determine if response status code is a server error.
	 * 
	 * @return bool
	 */
	public function isServerError()
	{
		return starts_with($this->getStatusCode(), 5);
	}

	/**
	 * Determine if response status code is OK.
	 * 
	 * @return bool
	 */
	public function isOk()
	{
		return $this->getStatusCode() == 200;
	}

	/**
	 * Determine if response status code is a not found.
	 * 
	 * @return bool
	 */
	public function isNotFound()
	{
		return $this->getStatusCode() == 404;
	}

	/**
	 * Determine if response status code is a forbidden.
	 * 
	 * @return bool
	 */
	public function isForbidden()
	{
		return $this->getStatusCode() == 403;
	}

	/**
	 * Determine if response status code is an internal server error.
	 * 
	 * @return bool
	 */
	public function isInternalServerError()
	{
		return $this->getStatusCode() == 500;
	}

	/**
	 * Get the exception message.
	 * 
	 * @return string
	 */
	public function message()
	{
		return $this->message;
	}

	/**
	 * Get the errors message bag.
	 * 
	 * @return \Illuminate\Support\MessageBag
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Get the errors message bag.
	 * 
	 * @return \Illuminate\Support\MessageBag
	 */
	public function errors()
	{
		return $this->errors;
	}

}
<?php namespace Dingo\Api\Http\ResponseFormat;

use Dingo\Api\Http\ResponseFormat\RequestAwareInterface;
use Illuminate\Http\Request as IlluminateRequest;

class JsonpResponseFormat extends \Dingo\Api\Http\ResponseFormat\JsonResponseFormat implements RequestAwareInterface
{

	/**
	 * Name of the parameter to check if we want to issue a json response
	 *
	 * @var string
	 */
	protected $callbackName = 'callback';

	/**
	 * The original request
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	public function setRequest($request)
	{
		$this->request = $request;
	}

	/**
	 * Has a callback parameter been specified in the request ?
	 * @return bool
	 */
	protected function hasValidCallback()
	{
		$this->callback = $this->request->input($this->callbackName);

		if (!empty($this->callback)) {
			return true;
		}

		return false;
	}

	/**
	 * Get the response content type.
	 *
	 * @return string
	 */
	public function getContentType()
	{
		if ($this->hasValidCallback()) {
			return 'application/javascript';
		}

		return 'application/json';
	}

	/**
	 * Encode the content to its JSON representation.
	 *
	 * @param  string  $content
	 * @return string
	 */
	protected function encode($content)
	{
		if ($this->hasValidCallback()) {
			return sprintf('%s(%s);', $this->callback, json_encode($content));
		}

		return json_encode($content);
	}

}

<?php namespace Dingo\Api\Http\ResponseFormat;

use Illuminate\Http\Request as IlluminateRequest;
use Dingo\Api\Http\ResponseFormat\JsonResponseFormat;

class JsonpResponseFormat extends JsonResponseFormat
{

	/**
	 * Name of the parameter to check if we want to issue a jsonp response
	 * if its not present it will fallback to json
	 *
	 * @var string
	 */
	protected $callbackName = 'callback';

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

		return parent::getContentType();
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

		return parent::encode($content);
	}

}

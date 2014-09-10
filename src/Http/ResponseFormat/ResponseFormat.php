<?php

namespace Dingo\Api\Http\ResponseFormat;

abstract class ResponseFormat
{
    /**
     * Illuminate request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Dingo Response instance
     *
     * @var \Dingo\Api\Http\Response
     */
    protected $apiResponse;

    /**
     * Set the request intance.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Dingo\Api\Http\ResponseFormat\ResponseFormat
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the response instance
     *
     * @param  \Dingo\Api\Http\Response  $response
     * @return \Dingo\Api\Http\ResponseFormat\ResponseFormat
     */
    public function setApiResponse($response)
    {
        $this->apiResponse = $response;

        return $this;
    }

    /**
     * Get the response instance
     *
     * @return \Dingo\Api\Http\Response
     */
    public function getApiResponse()
    {
        return $this->apiResponse;
    }

    /**
     * Format an Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    abstract public function formatEloquentModel($model);

    /**
     * Format an Eloquent collection.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $collection
     * @return string
     */
    abstract public function formatEloquentCollection($collection);

    /**
     * Format an array or instance implementing ArrayableInterface.
     *
     * @param  array|\Illuminate\Support\Contracts\ArrayableInterface  $content
     * @return string
     */
    abstract public function formatArray($content);

    /**
     * Format other response type such as a string or integer.
     *
     * @param  string  $content
     * @return string
     */
    abstract public function formatOther($content);

    /**
     * Get the response content type.
     *
     * @return string
     */
    abstract public function getContentType();
}

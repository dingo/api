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
     * Illuminate response instance.
     *
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * Set the request instance.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Dingo\Api\Http\ResponseFormat\ResponseFormat
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the response instance.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Dingo\Api\Http\ResponseFormat\ResponseFormat
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Format an Eloquent model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return string
     */
    abstract public function formatEloquentModel($model);

    /**
     * Format an Eloquent collection.
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     *
     * @return string
     */
    abstract public function formatEloquentCollection($collection);

    /**
     * Format an array or instance implementing ArrayableInterface.
     *
     * @param array|\Illuminate\Support\Contracts\ArrayableInterface $content
     *
     * @return string
     */
    abstract public function formatArray($content);

    /**
     * Get the response content type.
     *
     * @return string
     */
    abstract public function getContentType();
}

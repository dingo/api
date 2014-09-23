<?php

namespace Dingo\Api\Http\ResponseFormat;

use Illuminate\Support\Contracts\ArrayableInterface;

class JsonResponseFormat extends ResponseFormat
{
    /**
     * Format an Eloquent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    public function formatEloquentModel($model)
    {
        $key = str_singular($model->getTable());

        return $this->encode([$key => $model->toArray()]);
    }

    /**
     * Format an Eloquent collection.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $collection
     * @return string
     */
    public function formatEloquentCollection($collection)
    {
        if ($collection->isEmpty()) {
            return $this->encode([]);
        }

        $key = str_plural($collection->first()->getTable());

        return $this->encode([$key => $collection->toArray()]);
    }

    /**
     * Format other response type such as a string or integer.
     *
     * @param  string  $content
     * @return string
     */
    public function formatOther($content)
    {
        return $content;
    }

    /**
     * Format an array or instance implementing ArrayableInterface.
     *
     * @param  array|\Illuminate\Support\Contracts\ArrayableInterface  $content
     * @return string
     */
    public function formatArray($content)
    {
        $content = $this->morphToArray($content);

        array_walk_recursive($content, function (&$value) {
            $value = $this->morphToArray($value);
        });

        return $this->encode($content);
    }

    /**
     * Get the response content type.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'application/json';
    }

    /**
     * Morph a value to an array.
     *
     * @param  array|\Illuminate\Support\Contracts\ArrayableInterface  $value
     * @return array
     */
    protected function morphToArray($value)
    {
        return $value instanceof ArrayableInterface ? $value->toArray() : $value;
    }

    /**
     * Encode the content to its JSON representation.
     *
     * @param  string  $content
     * @return string
     */
    protected function encode($content)
    {
        return json_encode($content);
    }
}

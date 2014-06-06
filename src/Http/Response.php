<?php

namespace Dingo\Api\Http;

use RuntimeException;
use Dingo\Api\Transformer\Transformer;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Response extends IlluminateResponse
{
    /**
     * Array of registered formatters.
     *
     * @var array
     */
    protected static $formatters = [];

    /**
     * Transformer instance.
     *
     * @var \Dingo\Api\Transformer\Transformer
     */
    protected static $transformer;

    /**
     * Make an API response from an existing Illuminate response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Dingo\Api\Http\Response
     */
    public static function makeFromExisting(IlluminateResponse $response)
    {
        return new static($response->getOriginalContent(), $response->getStatusCode(), $response->headers->all());
    }

    /**
     * Morph the API response to the appropriate format.
     *
     * @oaram  string  $format
     * @return \Dingo\Api\Http\Response
     */
    public function morph($format = 'json')
    {
        $content = $this->getOriginalContent();

        if (static::$transformer->transformableResponse($content)) {
            $content = static::$transformer->transform($content);
        }

        $formatter = static::getFormatter($format);

        // Set the "Content-Type" header of the response to that which
        // is defined by the formatter being used. Before setting it
        // we'll get the original content type in case we need to
        // resort to that because of a response that is unable
        // to be formatted.
        $contentType = $this->headers->get('content-type');

        $this->headers->set('content-type', $formatter->getContentType());

        if ($content instanceof EloquentModel) {
            $content = $formatter->formatEloquentModel($content);
        } elseif ($content instanceof EloquentCollection) {
            $content = $formatter->formatEloquentCollection($content);
        } elseif (is_array($content) or $content instanceof ArrayableInterface) {
            $content = $formatter->formatArray($content);
        } else {
            $content = $formatter->formatOther($content);

            $this->headers->set('content-type', $contentType);
        }

        // Directly set the property because using setContent results in
        // the original content also being updated.
        $this->content = $content;

        return $this;
    }

    /**
     * Get the formatter based on the requested format type.
     *
     * @param  string  $format
     * @return \Dingo\Api\Http\Format\FormatInterface
     * @throws \RuntimeException
     */
    public static function getFormatter($format)
    {
        if (! isset(static::$formatters[$format])) {
            throw new RuntimeException('Response formatter "'.$format.'" has not been registered.');
        }

        return static::$formatters[$format];
    }

    /**
     * Set the response formatters.
     *
     * @param  array  $formatters
     * @return void
     */
    public static function setFormatters(array $formatters)
    {
        static::$formatters = $formatters;
    }

    /**
     * Set the transformer instance.
     *
     * @param  \Dingo\Api\Transformer\Transformer  $transformer
     * @return void
     */
    public static function setTransformer(Transformer $transformer)
    {
        static::$transformer = $transformer;
    }

    /**
     * Get the transformer instance.
     *
     * @return \Dingo\Api\Transformer\Transformer
     */
    public static function getTransformer()
    {
        return static::$transformer;
    }
}

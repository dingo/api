<?php

namespace Dingo\Api\Http;

use ArrayObject;
use UnexpectedValueException;
use Dingo\Api\Transformer\TransformerFactory;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class Response extends IlluminateResponse
{
    /**
     * Array of registered formatters.
     *
     * @var array
     */
    protected static $formatters = [];

    /**
     * Transformer factory instance.
     *
     * @var \Dingo\Api\Transformer\TransformerFactory
     */
    protected static $transformer;

    /**
     * Make an API response from an existing Illuminate response.
     *
     * @param \Illuminate\Http\Response $old
     *
     * @return \Dingo\Api\Http\Response
     */
    public static function makeFromExisting(IlluminateResponse $old)
    {
        $new = static::create($old->getOriginalContent(), $old->getStatusCode());

        $new->headers = $old->headers;

        return $new;
    }

    /**
     * Morph the API response to the appropriate format.
     *
     * @param string $format
     *
     * @return \Dingo\Api\Http\Response
     */
    public function morph($format = 'json')
    {
        $content = $this->getOriginalContent();

        if (isset(static::$transformer) && static::$transformer->transformableResponse($content)) {
            $content = static::$transformer->transform($content);
        }

        $formatter = static::getFormatter($format);

        $defaultContentType = $this->headers->get('content-type');

        $this->headers->set('content-type', $formatter->getContentType());

        if ($content instanceof EloquentModel) {
            $content = $formatter->formatEloquentModel($content);
        } elseif ($content instanceof EloquentCollection) {
            $content = $formatter->formatEloquentCollection($content);
        } elseif (is_array($content) || $content instanceof ArrayObject || $content instanceof ArrayableInterface) {
            $content = $formatter->formatArray($content);
        } else {
            $this->headers->set('content-type', $defaultContentType);
        }

        $this->content = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setContent($content)
    {
        // Attempt to set the content string, if we encounter an unexpected value
        // then we most likely have an object that cannot be type cast. In that
        // case we'll simply leave the content as null and set the original
        // content value the continue.
        try {
            return parent::setContent($content);
        } catch (UnexpectedValueException $exception) {
            $this->original = $content;

            return $this;
        }
    }

    /**
     * Get the formatter based on the requested format type.
     *
     * @param string $format
     *
     * @throws \RuntimeException
     *
     * @return \Dingo\Api\Http\Format\FormatInterface
     */
    public static function getFormatter($format)
    {
        if (! static::hasFormatter($format)) {
            throw new NotAcceptableHttpException('Unable to format response according to Accept header.');
        }

        return static::$formatters[$format];
    }

    /**
     * Determine if a response formatter has been registered.
     *
     * @param string $format
     *
     * @return bool
     */
    public static function hasFormatter($format)
    {
        return isset(static::$formatters[$format]);
    }

    /**
     * Set the response formatters.
     *
     * @param array $formatters
     *
     * @return void
     */
    public static function setFormatters(array $formatters)
    {
        static::$formatters = $formatters;
    }

    /**
     * Set the transformer factory instance.
     *
     * @param \Dingo\Api\Transformer\TransformerFactory $transformer
     *
     * @return void
     */
    public static function setTransformer(TransformerFactory $transformer)
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

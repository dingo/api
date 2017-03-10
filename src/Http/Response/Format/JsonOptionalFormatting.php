<?php

namespace Dingo\Api\Http\Response\Format;

trait JsonOptionalFormatting
{
    /*
     * Supported JSON pretty print indent styles.
     *
     * @var array
     */
    protected $indent_styles = [
        'tab',
        'space',
    ];

    /*
     * Indent styles, that are allowed to have various indent size.
     *
     * @var array
     */
    protected $has_various_indent_size = [
        'space',
    ];

    /*
     * Indent chars, associated with indent styles.
     *
     * @var array
     */
    protected $indent_chars = [
        'tab'   => "\t",
        'space' => " ",
    ];

    /*
     * JSON constants, that are allowed to be used as options while encoding.
     * Whitelist can be extended by other options in the future.
     *
     * @see http://php.net/manual/ru/json.constants.php
     *
     * @var array
     */
    protected $json_encode_options_whitelist = [
        JSON_PRETTY_PRINT,
    ];

    /**
     * Determine if JSON pretty print option is set to true.
     *
     * @return bool
     */
    protected function isJsonPrettyPrintEnabled()
    {
        return isset($this->options['prettyPrint']) &&
            true === $this->options['prettyPrint'];
    }

    /**
     * Determine if JSON custom indent style is set.
     *
     * @return bool
     */
    protected function isCustomIndentStyleRequired()
    {
        return $this->isJsonPrettyPrintEnabled() &&
            isset($this->options['indentStyle']) &&
            in_array($this->options['indentStyle'], $this->indent_styles);
    }

    /**
     * Perform JSON encode.
     *
     * @param string $content
     * @param array $json_encode_options
     *
     * @return string
     */
    protected function performJsonEncoding($content, array $json_encode_options = [])
    {
        $json_encode_options = $this->filterJsonEncodeOptions($json_encode_options);

        $options_bitmask = $this->calucateJsonEncodeOptionsBitmask($json_encode_options);

        return json_encode($content, $options_bitmask);
    }

    /**
     * Filter JSON encode options array against the whitelist array.
     *
     * @param array $json_encode_options
     *
     * @return array
     */
    protected function filterJsonEncodeOptions(array $json_encode_options)
    {
        return array_intersect($json_encode_options, $this->json_encode_options_whitelist);
    }

    /**
     * Sweep JSON encode options together to get options' bitmask.
     *
     * @param array $json_encode_options
     *
     * @return integer
     */
    protected function calucateJsonEncodeOptionsBitmask(array $json_encode_options)
    {
        return array_sum($json_encode_options);
    }

    /**
     * Indent pretty printed JSON string, using given indent style.
     *
     * @param string $json_string
     * @param string $indent_style
     * @param integer $default_indent_size
     *
     * @return string
     */
    protected function indentPrettyPrintedJson($json_string, $indent_style, $default_indent_size = 2)
    {
        $indent_char = $this->getIndentCharForIndentStyle($indent_style);
        $indent_size = $this->getPrettyPrintIndentSize() ?: $default_indent_size;

        // If the given indentation style is allowed to have various indent size
        // (number of chars, that are used to indent one level in each line),
        // indent the JSON string with given (or default) indent size.
        if ($this->hasVariousIndentSize($indent_style)) {
            return $this->peformIndentation($json_string, $indent_char, $indent_size);
        }

        // Otherwise following the convention, that indent styles, that does not
        // allowed to have various indent size (e.g. tab) are indented using
        // one tabulation character per one indent level in each line.
        return $this->peformIndentation($json_string, $indent_char);
    }

    /**
     * Get indent char for given indent style.
     *
     * @param string $indent_style
     *
     * @return string
     */
    protected function getIndentCharForIndentStyle($indent_style)
    {
        return $this->indent_chars[$indent_style];
    }

    /**
     * Get indent size for pretty printed JSON string.
     *
     * @return int|null
     */
    protected function getPrettyPrintIndentSize()
    {
        return isset($this->options['indentSize'])
            ? (int) $this->options['indentSize']
            : null;
    }

    /**
     * Determine if indent style is allowed to have various indent size.
     *
     * @param string $indent_style
     *
     * @return bool
     */
    protected function hasVariousIndentSize($indent_style)
    {
        return in_array($indent_style, $this->has_various_indent_size);
    }

    /**
     * Perform indentation for pretty printed JSON string with a given
     * indent char, repeated N times, as determined by indent size.
     *
     * @param string   $json_string     JSON string, which must be indented
     * @param string   $indent_char     Char, used for indent (default is tab)
     * @param integer  $indent_size     Number of times to repeat indent char per one indent level
     * @param integer  $default_spaces  Default number of indent spaces after json_encode()
     *
     * @return string
     */
    protected function peformIndentation($json_string, $indent_char = "\t", $indent_size = 1, $default_spaces = 4)
    {
        $pattern = '/(^|\G) {' . $default_spaces . '}/m';
        $replacement = str_repeat($indent_char, $indent_size) . '$1';

        return preg_replace($pattern, $replacement, $json_string);
    }
}

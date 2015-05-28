<?php

namespace Dingo\Api\Exception;

/**
 * Class ExceptionReplacementsTrait
 *
 * Example:
 * <code><?php
 * class MyException extends HttpException
 * {
 *      use ExceptionReplacementsTrait;
 * //...
 * }
 * $ex = new MyException(); // somewhere in API
 * $ex->setReplacements([':error' => $errors]);
 * throw $ex;
 * ?>
 * @package Dingo\Api\Exception
 */
class ExceptionReplacementsTrait {

    /**
     * Array of replacements from config/api.php and their values
     * @var array
     */
    public $replacements = [];

    /**
     * @return array
     */
    public function getReplacements()
    {
        return $this->replacements;
    }

    /**
     * @param array $replacements
     */
    public function setReplacements($replacements)
    {
        $this->replacements = $replacements;
    }
}
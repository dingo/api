<?php

namespace Dingo\Api\Tests\Http;

use Illuminate\Http\Request;
use Dingo\Api\Http\Validator;
use PHPUnit_Framework_TestCase;
use Illuminate\Container\Container;

class ValidatorTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->validator = new Validator(new Container);
    }

    public function testValidationFailsWithNoValidators()
    {
        $this->validator->replace([]);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when there were no validators.');
    }

    public function testValidationFails()
    {
        $this->validator->replace(['Dingo\Api\Tests\Stubs\HttpValidatorStub']);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when given a GET request.');
    }

    public function testValidationPasses()
    {
        $this->validator->replace(['Dingo\Api\Tests\Stubs\HttpValidatorStub']);

        $this->assertTrue($this->validator->validateRequest(Request::create('foo', 'POST')), 'Validation failed when given a POST request.');
    }
}

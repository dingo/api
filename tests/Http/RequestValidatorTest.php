<?php

namespace Dingo\Api\Tests\Http;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Dingo\Api\Http\RequestValidator;
use Dingo\Api\Tests\Stubs\HttpValidatorStub;
use Dingo\Api\Http\Parser\Accept as AcceptParser;

class RequestValidatorTest extends TestCase
{
    protected $container;

    public function setUp()
    {
        $this->container = new Container;
        $this->container->instance(AcceptParser::class, new AcceptParser('vnd', 'test', 'v1', 'json'));
        $this->validator = new RequestValidator($this->container);
    }

    public function testValidationFailsWithNoValidators()
    {
        $this->validator->replace([]);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when there were no validators.');
    }

    public function testValidationFails()
    {
        $this->validator->replace([HttpValidatorStub::class]);

        $this->assertFalse($this->validator->validateRequest(Request::create('foo', 'GET')), 'Validation passed when given a GET request.');
    }

    public function testValidationPasses()
    {
        $this->validator->replace([HttpValidatorStub::class]);

        $this->assertTrue($this->validator->validateRequest(Request::create('foo', 'POST')), 'Validation failed when given a POST request.');
    }
}

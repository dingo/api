<?php

namespace Dingo\Api\Tests\Http\Validation;

use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Http\Validation\Domain;

class DomainTest extends PHPUnit_Framework_TestCase
{
    public function testValidationFailsWithInvalidOrNullDomain()
    {
        $validator = new Domain('http://foo.bar');
        $this->assertFalse($validator->validate(Request::create('http://bar.foo', 'GET')), 'Validation passed when it should have failed with an invalid domain.');

        $validator = new Domain(null);
        $this->assertFalse($validator->validate(Request::create('http://bar.foo', 'GET')), 'Validation passed when it should have failed with a null domain.');
    }

    public function testValidationPasses()
    {
        $validator = new Domain('http://foo.bar');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar', 'GET')), 'Validation failed when it should have passed with a valid domain.');
    }

    public function testValidationPassesWithDifferentProtocols()
    {
        $validator = new Domain('ftp://foo.bar');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar', 'GET')), 'Validation failed when it should have passed with a valid domain.');

        $validator = new Domain('https://foo.bar');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar', 'GET')), 'Validation failed when it should have passed with a valid domain.');
    }
}

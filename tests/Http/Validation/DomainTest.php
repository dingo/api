<?php

namespace Dingo\Api\Tests\Http\Validation;

use Dingo\Api\Http\Validation\Domain;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;

class DomainTest extends BaseTestCase
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

    public function testValidationPassesWithPortOnDomain()
    {
        $validator = new Domain('http://foo.bar:8888');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar', 'GET')), 'Validation failed when it should have passed with a valid domain.');
    }

    public function testValidationPassesWithPortOnRequest()
    {
        $validator = new Domain('http://foo.bar');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar:8888', 'GET')), 'Validation failed when it should have passed with a valid domain.');
    }

    public function testValidationPassesWithPortOnDomainAndRequest()
    {
        $validator = new Domain('http://foo.bar:8888');
        $this->assertTrue($validator->validate(Request::create('http://foo.bar:8888', 'GET')), 'Validation failed when it should have passed with a valid domain.');
    }
}

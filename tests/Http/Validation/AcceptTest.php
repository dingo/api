<?php

namespace Dingo\Api\Tests\Http\Validation;

use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Dingo\Api\Http\Validation\Accept as AcceptValidator;
use Dingo\Api\Http\Parser\Accept as AcceptParser;

class AcceptTest extends PHPUnit_Framework_TestCase
{
    public function testValidationPassesForStrictModeAndOptionsRequests()
    {
        $parser = new AcceptParser('vnd', 'api', 'v1', 'json');
        $validator = new AcceptValidator($parser, true);

        $this->assertTrue($validator->validate(Request::create('bar', 'OPTIONS')), 'Validation failed when it should have passed with an options request.');
    }
}

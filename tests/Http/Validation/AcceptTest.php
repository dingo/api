<?php

namespace Dingo\Api\Tests\Http\Validation;

use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Dingo\Api\Http\Validation\Accept as AcceptValidator;
use Dingo\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;

class AcceptTest extends BaseTestCase
{
    public function testValidationPassesForStrictModeAndOptionsRequests()
    {
        $parser = new AcceptParser('vnd', 'api', 'v1', 'json');
        $validator = new AcceptValidator($parser, true);

        $this->assertTrue($validator->validate(Request::create('bar', 'OPTIONS')), 'Validation failed when it should have passed with an options request.');
    }
}

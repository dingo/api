<?php

namespace Dingo\Api\Tests\Http\Validation;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Dingo\Api\Http\Parser\Accept as AcceptParser;
use Dingo\Api\Http\Validation\Accept as AcceptValidator;

class AcceptTest extends TestCase
{
    public function testValidationPassesForStrictModeAndOptionsRequests()
    {
        $parser = new AcceptParser('vnd', 'api', 'v1', 'json');
        $validator = new AcceptValidator($parser, true);

        $this->assertTrue($validator->validate(Request::create('bar', 'OPTIONS')), 'Validation failed when it should have passed with an options request.');
    }
}

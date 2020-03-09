<?php

namespace Dingo\Api\Tests;

use Dingo\Api\Http\Response;
use Mockery;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        // make sure mocker is cleaned up
        Mockery::close();
        // reset response formatters on tear down
        Response::setFormatters([]);
        Response::setFormatsOptions([]);
    }

    public function setUp(): void
    {
        parent::setUp();
    }
}

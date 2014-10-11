<?php

namespace Dingo\Api\Tests\Stubs;

use Dingo\Api\Transformer\TransformableInterface;

class UserContractStub extends UserStub implements TransformableInterface
{
    public function getTransformer()
    {
        return new UserTransformerStub;
    }
}

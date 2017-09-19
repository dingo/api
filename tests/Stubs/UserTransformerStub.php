<?php

namespace Dingo\Api\Tests\Stubs;

class UserTransformerStub
{
    public function transform(UserStub $user)
    {
        return [
            'name' => $user->name,
        ];
    }
}

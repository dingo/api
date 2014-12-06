<?php

namespace Dingo\Api\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class EloquentModelStub extends Model
{
    protected $table = 'app_user';

    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}

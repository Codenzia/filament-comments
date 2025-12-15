<?php

namespace Codenzia\FilamentComments\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}

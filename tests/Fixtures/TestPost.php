<?php

namespace Codenzia\FilamentComments\Tests\Fixtures;

use Codenzia\FilamentComments\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;

class TestPost extends Model
{
    use HasComments;

    protected $table = 'posts';
    protected $guarded = [];
}

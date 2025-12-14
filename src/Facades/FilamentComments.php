<?php

namespace Codenzia\FilamentComments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codenzia\FilamentComments\FilamentComments
 */
class FilamentComments extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Codenzia\FilamentComments\FilamentComments::class;
    }
}

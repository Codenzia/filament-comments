<?php

namespace Codenzia\FilamentComments\Tests;

use Codenzia\FilamentComments\FilamentCommentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FilamentCommentsServiceProvider::class,
        ];
    }
}

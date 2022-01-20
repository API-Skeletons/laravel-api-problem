<?php

namespace ApiSkeletonsTest\Laravel\ApiProblem;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \ApiSkeletons\Laravel\ApiProblem\ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
    }
}

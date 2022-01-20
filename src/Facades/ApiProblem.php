<?php

declare(strict_types=1);

namespace ApiSkeletons\Laravel\ApiProblem\Facades;

use Illuminate\Support\Facades\Facade;

class ApiProblem extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ApiProblem';
    }
}

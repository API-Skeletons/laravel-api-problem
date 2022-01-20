<?php

declare(strict_types=1);

namespace ApiSkeletons\Laravel\ApiProblem\Exception;

use InvalidArgumentException as PHPInvalidArgumentException;

class InvalidArgumentException extends PHPInvalidArgumentException implements ExceptionInterface
{
}

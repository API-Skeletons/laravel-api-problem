<?php

declare(strict_types=1);

namespace ApiSkeletons\Laravel\ApiProblem\Exception;

use Traversable;

/**
 * Interface for exceptions that can provide additional API Problem details.
 */
interface ProblemExceptionInterface
{
    public function getAdditionalDetails(): array|Traversable|null;

    public function getType(): ?string;

    public function getTitle(): ?string;
}

<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Backstage\UpdateProjectTranslation;

final class UpdateProjectTranslationOutput
{
    /**
     * @param array<string, array{filled: int, total: int}> $coverage
     */
    public function __construct(public readonly array $coverage)
    {
    }
}

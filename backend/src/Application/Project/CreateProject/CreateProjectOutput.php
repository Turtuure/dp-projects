<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\CreateProject;

final class CreateProjectOutput
{
    public function __construct(
        public readonly ?array $project,
        public readonly ?string $error = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\GetProject;

final class GetProjectOutput
{
    public function __construct(
        public readonly ?array $project,
    ) {}
}

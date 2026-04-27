<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\ListProjects;

final class ListProjectsOutput
{
    public function __construct(
        public readonly array $projects,
    ) {}
}

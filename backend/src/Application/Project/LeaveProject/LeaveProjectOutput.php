<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\LeaveProject;

final class LeaveProjectOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}
}

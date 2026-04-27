<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\ArchiveProject;

final class ArchiveProjectOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}
}

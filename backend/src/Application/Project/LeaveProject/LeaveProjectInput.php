<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\LeaveProject;

use Daems\Domain\Auth\ActingUser;

final class LeaveProjectInput
{
    public function __construct(
        public readonly ActingUser $acting,
        public readonly string $slug,
    ) {}
}

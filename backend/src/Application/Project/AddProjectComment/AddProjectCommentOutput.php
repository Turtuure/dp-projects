<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\AddProjectComment;

final class AddProjectCommentOutput
{
    public function __construct(
        public readonly ?array $comment,
        public readonly ?string $error = null,
    ) {}
}

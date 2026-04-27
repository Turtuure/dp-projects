<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\LikeProjectComment;

use Daems\Domain\Auth\ActingUser;

final class LikeProjectCommentInput
{
    public function __construct(
        public readonly ActingUser $acting,
        public readonly string $commentId,
    ) {}
}

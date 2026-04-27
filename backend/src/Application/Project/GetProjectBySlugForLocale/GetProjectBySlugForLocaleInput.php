<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Application\Project\GetProjectBySlugForLocale;

use Daems\Domain\Locale\SupportedLocale;
use Daems\Domain\Tenant\TenantId;

final class GetProjectBySlugForLocaleInput
{
    public function __construct(
        public readonly TenantId $tenantId,
        public readonly SupportedLocale $locale,
        public readonly string $slug,
    ) {
    }
}

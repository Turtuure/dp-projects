<?php
declare(strict_types=1);

namespace DaemsModule\Projects\Frontend\Backstage\Widgets;

use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\Widget;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Dashboard\WidgetSpan;
use Daems\Domain\Tenant\TenantId;
use Daems\Domain\User\User;
use Daems\Infrastructure\Dashboard\WidgetRenderer;

final class ProjectsKpiWidget extends Widget
{
    public function __construct() {}

    public function id(): string             { return 'projects.projects_kpi'; }
    public function category(): WidgetCategory { return WidgetCategory::Numbers; }
    public function defaultSpan(): WidgetSpan  { return WidgetSpan::of(1); }
    public function minRole(): MinRole         { return MinRole::Admin; }
    public function module(): string           { return 'projects'; }
    public function labelKey(): string         { return 'backstage.dashboard.widget.projects_kpi.label'; }
    public function descriptionKey(): string   { return 'backstage.dashboard.widget.projects_kpi.description'; }

    public function render(TenantId $tenantId, User $user): string
    {
        $d = $this->data($tenantId);
        return WidgetRenderer::kpi($d['value'], $d['change']);
    }

    public function data(TenantId $tenantId): array
    {
        // TODO(v1+): wire real active-project count from ListProjectsStats
        // (use case requires ActingUser; widget contract only exposes TenantId).
        return ['value' => 0, 'change' => 0.0];
    }
}

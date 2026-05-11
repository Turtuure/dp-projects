<?php
declare(strict_types=1);

namespace DaemsModule\Projects\Frontend\Backstage\Widgets;

use Daems\Application\Admin\GetAdminStats\GetAdminStats;
use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\Widget;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Dashboard\WidgetSpan;
use Daems\Domain\Tenant\TenantId;
use Daems\Domain\User\User;
use Daems\Frontend\I18n;
use Daems\Infrastructure\Dashboard\WidgetRenderer;

final class ProjectsKpiWidget extends Widget
{
    private const ICON = '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>';

    public function __construct(
        private readonly GetAdminStats $getAdminStats,
    ) {}

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
        return WidgetRenderer::kpi(
            value:         (int) $d['value'],
            change:        (float) $d['change'],
            label:         I18n::t($this->labelKey()),
            color:         'purple',
            iconSvg:       self::ICON,
            sparklineId:   'spark-' . str_replace('.', '-', $this->id()),
            sparklineData: $d['sparkline'] ?? null,
        );
    }

    public function data(TenantId $tenantId): array
    {
        $stats = $this->getAdminStats->execute($tenantId);
        return [
            'value'     => $stats->activeProjects,
            'change'    => $stats->projectsChange,
            'sparkline' => $stats->projectsSparkline,
        ];
    }
}

<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Tests\Unit\Frontend\Backstage\Widgets;

use Daems\Application\Admin\GetAdminStats\GetAdminStats;
use Daems\Domain\Admin\AdminStats;
use Daems\Domain\Admin\AdminStatsRepositoryInterface;
use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Tenant\TenantId;
use DaemsModule\Projects\Frontend\Backstage\Widgets\ProjectsKpiWidget;
use PHPUnit\Framework\TestCase;

final class ProjectsKpiWidgetTest extends TestCase
{
    public function test_metadata_for_projects_kpi_widget(): void
    {
        $w = new ProjectsKpiWidget($this->fakeStats());

        self::assertSame('projects.projects_kpi', $w->id());
        self::assertSame(WidgetCategory::Numbers, $w->category());
        self::assertSame(1, $w->defaultSpan()->value());
        self::assertSame(MinRole::Admin, $w->minRole());
        self::assertSame('projects', $w->module());
    }

    public function test_projects_kpi_data(): void
    {
        $w = new ProjectsKpiWidget($this->fakeStats());
        $d = $w->data(TenantId::generate());
        self::assertSame(9, $d['value']);
        self::assertSame(2.5, $d['change']);
    }

    public function test_render_projects_kpi_returns_html(): void
    {
        $w    = new ProjectsKpiWidget($this->fakeStats());
        $html = $w->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    private function fakeStats(): GetAdminStats
    {
        $repo = new class implements AdminStatsRepositoryInterface {
            public function getStatsForTenant(TenantId $tenantId): AdminStats
            {
                return new AdminStats(
                    members: 0,
                    pendingApplications: 0,
                    upcomingEvents: 0,
                    activeProjects: 9,
                    membersSparkline: [],
                    applicationsSparkline: [],
                    eventsSparkline: [],
                    projectsSparkline: [],
                    forumSparkline: [],
                    insightsSparkline: [],
                    membersChange: 0.0,
                    applicationsChange: 0.0,
                    eventsChange: 0.0,
                    projectsChange: 2.5,
                    memberGrowth: ['labels' => [], 'series' => []],
                );
            }

            /** @return array{ labels: string[], series: int[] } */
            public function getMemberGrowthForTenant(string $period, TenantId $tenantId): array
            {
                return ['labels' => [], 'series' => []];
            }

            /** @return array{supporting:int, basic:int, full:int, honorary:int} */
            public function getMembersByTier(TenantId $tenantId): array
            {
                return ['supporting' => 0, 'basic' => 0, 'full' => 0, 'honorary' => 0];
            }
        };

        return new GetAdminStats($repo);
    }

    private function fakeUser(): \Daems\Domain\User\User
    {
        $class = new \ReflectionClass(\Daems\Domain\User\User::class);
        return $class->newInstanceWithoutConstructor();
    }
}

<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Tests\Unit\Frontend\Backstage\Widgets;

use Daems\Domain\Dashboard\MinRole;
use Daems\Domain\Dashboard\WidgetCategory;
use Daems\Domain\Tenant\TenantId;
use DaemsModule\Projects\Frontend\Backstage\Widgets\ProjectsKpiWidget;
use PHPUnit\Framework\TestCase;

final class ProjectsKpiWidgetTest extends TestCase
{
    public function test_metadata_for_projects_kpi_widget(): void
    {
        $w = new ProjectsKpiWidget();

        self::assertSame('projects.projects_kpi', $w->id());
        self::assertSame(WidgetCategory::Numbers, $w->category());
        self::assertSame(1, $w->defaultSpan()->value());
        self::assertSame(MinRole::Admin, $w->minRole());
        self::assertSame('projects', $w->module());
    }

    public function test_projects_kpi_data_stub(): void
    {
        $w = new ProjectsKpiWidget();
        $d = $w->data(TenantId::generate());
        self::assertSame(0, $d['value']);
        self::assertSame(0.0, $d['change']);
    }

    public function test_render_projects_kpi_returns_html(): void
    {
        $w    = new ProjectsKpiWidget();
        $html = $w->render(TenantId::generate(), $this->fakeUser());
        self::assertNotSame('', $html);
        self::assertStringContainsString('card', $html);
    }

    private function fakeUser(): \Daems\Domain\User\User
    {
        $class = new \ReflectionClass(\Daems\Domain\User\User::class);
        return $class->newInstanceWithoutConstructor();
    }
}

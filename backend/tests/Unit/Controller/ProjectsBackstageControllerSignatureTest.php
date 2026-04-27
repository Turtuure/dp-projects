<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Tests\Unit\Controller;

use DaemsModule\Projects\Application\Backstage\AdminUpdateProject\AdminUpdateProject;
use DaemsModule\Projects\Application\Backstage\ApproveProjectProposal\ApproveProjectProposal;
use DaemsModule\Projects\Application\Backstage\ChangeProjectStatus\ChangeProjectStatus;
use DaemsModule\Projects\Application\Backstage\CreateProjectAsAdmin\CreateProjectAsAdmin;
use DaemsModule\Projects\Application\Backstage\DeleteProjectCommentAsAdmin\DeleteProjectCommentAsAdmin;
use DaemsModule\Projects\Application\Backstage\GetProjectWithAllTranslations\GetProjectWithAllTranslations;
use DaemsModule\Projects\Application\Backstage\ListProjectCommentsForAdmin\ListProjectCommentsForAdmin;
use DaemsModule\Projects\Application\Backstage\ListProjectsForAdmin\ListProjectsForAdmin;
use DaemsModule\Projects\Application\Backstage\Projects\ListProjectsStats\ListProjectsStats;
use DaemsModule\Projects\Application\Backstage\RejectProjectProposal\RejectProjectProposal;
use DaemsModule\Projects\Application\Backstage\SetProjectFeatured\SetProjectFeatured;
use DaemsModule\Projects\Application\Backstage\UpdateProjectTranslation\UpdateProjectTranslation;
use DaemsModule\Projects\Controller\ProjectsBackstageController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class ProjectsBackstageControllerSignatureTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists(ProjectsBackstageController::class));
    }

    public function test_constructor_takes_all_12_use_cases(): void
    {
        $ref = new ReflectionClass(ProjectsBackstageController::class);
        $ctor = $ref->getConstructor();
        $this->assertNotNull($ctor);

        $expected = [
            ListProjectsForAdmin::class,
            CreateProjectAsAdmin::class,
            AdminUpdateProject::class,
            ChangeProjectStatus::class,
            SetProjectFeatured::class,
            ListProjectCommentsForAdmin::class,
            DeleteProjectCommentAsAdmin::class,
            ListProjectsStats::class,
            GetProjectWithAllTranslations::class,
            UpdateProjectTranslation::class,
            ApproveProjectProposal::class,
            RejectProjectProposal::class,
        ];

        $params = $ctor->getParameters();
        $this->assertGreaterThanOrEqual(count($expected), count($params), 'constructor must take at least 12 use cases');

        $actualTypes = array_map(static function ($p) {
            $type = $p->getType();
            return $type instanceof ReflectionNamedType ? $type->getName() : null;
        }, $params);

        foreach ($expected as $exp) {
            $this->assertContains($exp, $actualTypes, "missing constructor param of type $exp");
        }
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function methodNamesProvider(): array
    {
        return [
            ['listProjectsAdmin'],
            ['createProjectAdmin'],
            ['updateProjectAdmin'],
            ['changeProjectStatus'],
            ['setProjectFeatured'],
            ['listProjectComments'],
            ['deleteProjectComment'],
            ['statsProjects'],
            ['getProjectWithTranslations'],
            ['updateProjectTranslation'],
            ['approveProjectProposal'],
            ['rejectProjectProposal'],
        ];
    }

    /**
     * @dataProvider methodNamesProvider
     */
    public function test_method_exists(string $method): void
    {
        $ref = new ReflectionClass(ProjectsBackstageController::class);
        $this->assertTrue($ref->hasMethod($method), "missing public method: $method");
        $m = $ref->getMethod($method);
        $this->assertTrue($m->isPublic(), "$method must be public");
    }
}

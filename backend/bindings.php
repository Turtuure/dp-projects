<?php

declare(strict_types=1);

use Daems\Domain\Project\ProjectCommentModerationAuditRepositoryInterface;
use Daems\Domain\Project\ProjectProposalRepositoryInterface;
use Daems\Domain\Project\ProjectRepositoryInterface;
use Daems\Infrastructure\Framework\Container\Container;
use Daems\Infrastructure\Framework\Database\Connection;
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
use DaemsModule\Projects\Application\Project\AddProjectComment\AddProjectComment;
use DaemsModule\Projects\Application\Project\AddProjectUpdate\AddProjectUpdate;
use DaemsModule\Projects\Application\Project\ArchiveProject\ArchiveProject;
use DaemsModule\Projects\Application\Project\CreateProject\CreateProject;
use DaemsModule\Projects\Application\Project\GetProject\GetProject;
use DaemsModule\Projects\Application\Project\GetProjectBySlugForLocale\GetProjectBySlugForLocale;
use DaemsModule\Projects\Application\Project\JoinProject\JoinProject;
use DaemsModule\Projects\Application\Project\LeaveProject\LeaveProject;
use DaemsModule\Projects\Application\Project\LikeProjectComment\LikeProjectComment;
use DaemsModule\Projects\Application\Project\ListProjects\ListProjects;
use DaemsModule\Projects\Application\Project\ListProjectsForLocale\ListProjectsForLocale;
use DaemsModule\Projects\Application\Project\SubmitProjectProposal\SubmitProjectProposal;
use DaemsModule\Projects\Application\Project\UpdateProject\UpdateProject;
use DaemsModule\Projects\Controller\ProjectController;
use DaemsModule\Projects\Controller\ProjectsBackstageController;
use DaemsModule\Projects\Infrastructure\SqlProjectCommentModerationAuditRepository;
use DaemsModule\Projects\Infrastructure\SqlProjectProposalRepository;
use DaemsModule\Projects\Infrastructure\SqlProjectRepository;

/**
 * Projects module — production DI bindings.
 *
 * Loaded by ModuleRegistry::registerBindings() AFTER core bootstrap/app.php,
 * so these bindings WIN over any older Project bindings still present there
 * (a later wave will remove the originals from core).
 *
 * Architecture: Domain stays in core. The 3 repository INTERFACES live at
 * \Daems\Domain\Project\Project*RepositoryInterface and are bound here to the
 * module's SQL implementations under \DaemsModule\Projects\Infrastructure\.
 *
 * Inventory: 3 repos + 13 public + 12 admin use cases + 2 controllers = 30 bindings.
 */
return static function (Container $container): void {
    // ---------------------------------------------------------------------
    // 3× Repository bindings — CORE interfaces → MODULE SQL impls
    // ---------------------------------------------------------------------
    $container->singleton(
        ProjectRepositoryInterface::class,
        static fn(Container $c) => new SqlProjectRepository($c->make(Connection::class)),
    );
    $container->singleton(
        ProjectCommentModerationAuditRepositoryInterface::class,
        static fn(Container $c) => new SqlProjectCommentModerationAuditRepository(
            $c->make(Connection::class),
        ),
    );
    $container->singleton(
        ProjectProposalRepositoryInterface::class,
        static fn(Container $c) => new SqlProjectProposalRepository($c->make(Connection::class)),
    );

    // ---------------------------------------------------------------------
    // 13× Public use cases (DaemsModule\Projects\Application\Project\*)
    // ---------------------------------------------------------------------
    $container->bind(
        ListProjects::class,
        static fn(Container $c) => new ListProjects($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        GetProject::class,
        static fn(Container $c) => new GetProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        CreateProject::class,
        static fn(Container $c) => new CreateProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        UpdateProject::class,
        static fn(Container $c) => new UpdateProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        ArchiveProject::class,
        static fn(Container $c) => new ArchiveProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        AddProjectComment::class,
        static fn(Container $c) => new AddProjectComment(
            $c->make(ProjectRepositoryInterface::class),
            $c->make(\Daems\Domain\User\UserRepositoryInterface::class),
        ),
    );
    $container->bind(
        LikeProjectComment::class,
        static fn(Container $c) => new LikeProjectComment($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        JoinProject::class,
        static fn(Container $c) => new JoinProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        LeaveProject::class,
        static fn(Container $c) => new LeaveProject($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        AddProjectUpdate::class,
        static fn(Container $c) => new AddProjectUpdate(
            $c->make(ProjectRepositoryInterface::class),
            $c->make(\Daems\Domain\User\UserRepositoryInterface::class),
        ),
    );
    $container->bind(
        SubmitProjectProposal::class,
        static fn(Container $c) => new SubmitProjectProposal(
            $c->make(ProjectProposalRepositoryInterface::class),
            $c->make(\Daems\Domain\User\UserRepositoryInterface::class),
        ),
    );
    $container->bind(
        ListProjectsForLocale::class,
        static fn(Container $c) => new ListProjectsForLocale($c->make(ProjectRepositoryInterface::class)),
    );
    $container->bind(
        GetProjectBySlugForLocale::class,
        static fn(Container $c) => new GetProjectBySlugForLocale($c->make(ProjectRepositoryInterface::class)),
    );

    // ---------------------------------------------------------------------
    // 12× Admin use cases (DaemsModule\Projects\Application\Backstage\*)
    // ---------------------------------------------------------------------
    $container->bind(
        ListProjectsForAdmin::class,
        static fn(Container $c) => new ListProjectsForAdmin(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        CreateProjectAsAdmin::class,
        static fn(Container $c) => new CreateProjectAsAdmin(
            $c->make(ProjectRepositoryInterface::class),
            $c->make(\Daems\Domain\Shared\IdGeneratorInterface::class),
        ),
    );
    $container->bind(
        AdminUpdateProject::class,
        static fn(Container $c) => new AdminUpdateProject(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        ChangeProjectStatus::class,
        static fn(Container $c) => new ChangeProjectStatus(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        SetProjectFeatured::class,
        static fn(Container $c) => new SetProjectFeatured(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        ApproveProjectProposal::class,
        static fn(Container $c) => new ApproveProjectProposal(
            $c->make(ProjectProposalRepositoryInterface::class),
            $c->make(ProjectRepositoryInterface::class),
            $c->make(\Daems\Domain\Dismissal\AdminApplicationDismissalRepositoryInterface::class),
            $c->make(\Daems\Domain\Shared\TransactionManagerInterface::class),
            $c->make(\Daems\Domain\Shared\Clock::class),
            $c->make(\Daems\Domain\Shared\IdGeneratorInterface::class),
        ),
    );
    $container->bind(
        RejectProjectProposal::class,
        static fn(Container $c) => new RejectProjectProposal(
            $c->make(ProjectProposalRepositoryInterface::class),
            $c->make(\Daems\Domain\Dismissal\AdminApplicationDismissalRepositoryInterface::class),
            $c->make(\Daems\Domain\Shared\TransactionManagerInterface::class),
            $c->make(\Daems\Domain\Shared\Clock::class),
        ),
    );
    $container->bind(
        ListProjectCommentsForAdmin::class,
        static fn(Container $c) => new ListProjectCommentsForAdmin(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        DeleteProjectCommentAsAdmin::class,
        static fn(Container $c) => new DeleteProjectCommentAsAdmin(
            $c->make(ProjectRepositoryInterface::class),
            $c->make(ProjectCommentModerationAuditRepositoryInterface::class),
            $c->make(\Daems\Domain\Shared\Clock::class),
            $c->make(\Daems\Domain\Shared\IdGeneratorInterface::class),
        ),
    );
    $container->bind(
        ListProjectsStats::class,
        static fn(Container $c) => new ListProjectsStats(
            $c->make(ProjectRepositoryInterface::class),
            $c->make(ProjectProposalRepositoryInterface::class),
        ),
    );
    $container->bind(
        GetProjectWithAllTranslations::class,
        static fn(Container $c) => new GetProjectWithAllTranslations(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );
    $container->bind(
        UpdateProjectTranslation::class,
        static fn(Container $c) => new UpdateProjectTranslation(
            $c->make(ProjectRepositoryInterface::class),
        ),
    );

    // Dashboard widgets — registered with the platform's WidgetRegistry singleton
    // (already bound by daems-platform/bootstrap/app.php before module bindings run).
    $registry = $container->make(\Daems\Domain\Dashboard\WidgetRegistry::class);
    $registry->register(new \DaemsModule\Projects\Frontend\Backstage\Widgets\ProjectsKpiWidget());

    // ---------------------------------------------------------------------
    // 2× Controllers
    // ---------------------------------------------------------------------
    $container->bind(
        ProjectController::class,
        static fn(Container $c) => new ProjectController(
            $c->make(ListProjects::class),
            $c->make(GetProject::class),
            $c->make(CreateProject::class),
            $c->make(UpdateProject::class),
            $c->make(ArchiveProject::class),
            $c->make(AddProjectComment::class),
            $c->make(LikeProjectComment::class),
            $c->make(JoinProject::class),
            $c->make(LeaveProject::class),
            $c->make(AddProjectUpdate::class),
            $c->make(SubmitProjectProposal::class),
            $c->make(ListProjectsForLocale::class),
            $c->make(GetProjectBySlugForLocale::class),
        ),
    );
    $container->bind(
        ProjectsBackstageController::class,
        static fn(Container $c) => new ProjectsBackstageController(
            $c->make(ListProjectsForAdmin::class),
            $c->make(CreateProjectAsAdmin::class),
            $c->make(AdminUpdateProject::class),
            $c->make(ChangeProjectStatus::class),
            $c->make(SetProjectFeatured::class),
            $c->make(ListProjectCommentsForAdmin::class),
            $c->make(DeleteProjectCommentAsAdmin::class),
            $c->make(ListProjectsStats::class),
            $c->make(GetProjectWithAllTranslations::class),
            $c->make(UpdateProjectTranslation::class),
            $c->make(ApproveProjectProposal::class),
            $c->make(RejectProjectProposal::class),
        ),
    );
};

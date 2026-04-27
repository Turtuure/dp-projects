<?php

declare(strict_types=1);

use Daems\Infrastructure\Framework\Container\Container;
use Daems\Infrastructure\Framework\Http\Middleware\AuthMiddleware;
use Daems\Infrastructure\Framework\Http\Middleware\LocaleMiddleware;
use Daems\Infrastructure\Framework\Http\Middleware\TenantContextMiddleware;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use Daems\Infrastructure\Framework\Http\Router;
use DaemsModule\Projects\Controller\ProjectController;
use DaemsModule\Projects\Controller\ProjectsBackstageController;

/**
 * Projects module — route registrations.
 *
 * 13 public + 12 backstage = 25 routes. Middleware lists match core routes/api.php exactly.
 */
return static function (Router $router, Container $container): void {
    // ---------------------------------------------------------------------
    // Public — Projects (13 routes)
    // ---------------------------------------------------------------------
    $router->get('/api/v1/projects', static function (Request $req) use ($container): Response {
        return $container->make(ProjectController::class)->indexLocalized($req);
    }, [TenantContextMiddleware::class, LocaleMiddleware::class]);

    $router->get('/api/v1/projects/{slug}', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->showLocalized($req, $params);
    }, [TenantContextMiddleware::class, LocaleMiddleware::class]);

    // Projects — legacy non-localized (kept for backward compat)
    $router->get('/api/v1/projects-legacy', static function (Request $req) use ($container): Response {
        return $container->make(ProjectController::class)->index($req);
    }, [TenantContextMiddleware::class]);

    $router->get('/api/v1/projects-legacy/{slug}', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->show($req, $params);
    }, [TenantContextMiddleware::class]);

    // Projects — protected mutations
    $router->post('/api/v1/projects', static function (Request $req) use ($container): Response {
        return $container->make(ProjectController::class)->create($req);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->update($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}/archive', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->archive($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}/join', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->join($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}/leave', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->leave($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}/comments', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->addComment($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/project-comments/{id}/like', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->likeComment($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/projects/{slug}/updates', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectController::class)->addUpdate($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/project-proposals', static function (Request $req) use ($container): Response {
        return $container->make(ProjectController::class)->propose($req);
    }, [TenantContextMiddleware::class, LocaleMiddleware::class, AuthMiddleware::class]);

    // ---------------------------------------------------------------------
    // Backstage — Projects admin (12 routes)
    // ---------------------------------------------------------------------
    $router->get('/api/v1/backstage/projects/stats', static function (Request $req) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->statsProjects($req);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->get('/api/v1/backstage/projects', static function (Request $req) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->listProjectsAdmin($req);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects', static function (Request $req) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->createProjectAdmin($req);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects/{id}', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->updateProjectAdmin($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects/{id}/status', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->changeProjectStatus($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects/{id}/featured', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->setProjectFeatured($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/proposals/{id}/approve', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->approveProjectProposal($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/proposals/{id}/reject', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->rejectProjectProposal($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->get('/api/v1/backstage/projects/{id}/translations', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->getProjectWithTranslations($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects/{id}/translations/{locale}', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->updateProjectTranslation($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->get('/api/v1/backstage/comments/recent', static function (Request $req) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->listProjectComments($req);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);

    $router->post('/api/v1/backstage/projects/{id}/comments/{comment_id}/delete', static function (Request $req, array $params) use ($container): Response {
        return $container->make(ProjectsBackstageController::class)->deleteProjectComment($req, $params);
    }, [TenantContextMiddleware::class, AuthMiddleware::class]);
};

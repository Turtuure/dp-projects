<?php

declare(strict_types=1);

namespace DaemsModule\Projects\Controller;

use Daems\Domain\Auth\ForbiddenException;
use Daems\Domain\Locale\InvalidLocaleException;
use Daems\Domain\Shared\NotFoundException;
use Daems\Domain\Shared\ValidationException;
use Daems\Domain\Tenant\Tenant;
use Daems\Infrastructure\Framework\Http\Request;
use Daems\Infrastructure\Framework\Http\Response;
use DaemsModule\Projects\Application\Backstage\AdminUpdateProject\AdminUpdateProject;
use DaemsModule\Projects\Application\Backstage\AdminUpdateProject\AdminUpdateProjectInput;
use DaemsModule\Projects\Application\Backstage\ApproveProjectProposal\ApproveProjectProposal;
use DaemsModule\Projects\Application\Backstage\ApproveProjectProposal\ApproveProjectProposalInput;
use DaemsModule\Projects\Application\Backstage\ChangeProjectStatus\ChangeProjectStatus;
use DaemsModule\Projects\Application\Backstage\ChangeProjectStatus\ChangeProjectStatusInput;
use DaemsModule\Projects\Application\Backstage\CreateProjectAsAdmin\CreateProjectAsAdmin;
use DaemsModule\Projects\Application\Backstage\CreateProjectAsAdmin\CreateProjectAsAdminInput;
use DaemsModule\Projects\Application\Backstage\DeleteProjectCommentAsAdmin\DeleteProjectCommentAsAdmin;
use DaemsModule\Projects\Application\Backstage\DeleteProjectCommentAsAdmin\DeleteProjectCommentAsAdminInput;
use DaemsModule\Projects\Application\Backstage\GetProjectWithAllTranslations\GetProjectWithAllTranslations;
use DaemsModule\Projects\Application\Backstage\GetProjectWithAllTranslations\GetProjectWithAllTranslationsInput;
use DaemsModule\Projects\Application\Backstage\ListProjectCommentsForAdmin\ListProjectCommentsForAdmin;
use DaemsModule\Projects\Application\Backstage\ListProjectCommentsForAdmin\ListProjectCommentsForAdminInput;
use DaemsModule\Projects\Application\Backstage\ListProjectsForAdmin\ListProjectsForAdmin;
use DaemsModule\Projects\Application\Backstage\ListProjectsForAdmin\ListProjectsForAdminInput;
use DaemsModule\Projects\Application\Backstage\Projects\ListProjectsStats\ListProjectsStats;
use DaemsModule\Projects\Application\Backstage\Projects\ListProjectsStats\ListProjectsStatsInput;
use DaemsModule\Projects\Application\Backstage\RejectProjectProposal\RejectProjectProposal;
use DaemsModule\Projects\Application\Backstage\RejectProjectProposal\RejectProjectProposalInput;
use DaemsModule\Projects\Application\Backstage\SetProjectFeatured\SetProjectFeatured;
use DaemsModule\Projects\Application\Backstage\SetProjectFeatured\SetProjectFeaturedInput;
use DaemsModule\Projects\Application\Backstage\UpdateProjectTranslation\UpdateProjectTranslation;
use DaemsModule\Projects\Application\Backstage\UpdateProjectTranslation\UpdateProjectTranslationInput;

final class ProjectsBackstageController
{
    public function __construct(
        private readonly ListProjectsForAdmin $listProjects,
        private readonly CreateProjectAsAdmin $createProject,
        private readonly AdminUpdateProject $updateProject,
        private readonly ChangeProjectStatus $changeProjectStatus,
        private readonly SetProjectFeatured $setProjectFeatured,
        private readonly ListProjectCommentsForAdmin $listProjectComments,
        private readonly DeleteProjectCommentAsAdmin $deleteProjectComment,
        private readonly ListProjectsStats $listProjectsStats,
        private readonly GetProjectWithAllTranslations $getProjectWithAllTranslations,
        private readonly UpdateProjectTranslation $updateProjectTranslation,
        private readonly ApproveProjectProposal $approveProposal,
        private readonly RejectProjectProposal $rejectProposal,
    ) {
    }

    public function listProjectsAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();
        try {
            $featuredRaw = $request->input('featured');
            $out = $this->listProjects->execute(new ListProjectsForAdminInput(
                $acting,
                $request->string('status'),
                $request->string('category'),
                $featuredRaw !== null ? (bool) $featuredRaw : null,
                $request->string('q'),
            ));
            return Response::json($out->toArray());
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        }
    }

    public function createProjectAdmin(Request $request): Response
    {
        $acting = $request->requireActingUser();
        try {
            $out = $this->createProject->execute(new CreateProjectAsAdminInput(
                $acting,
                (string) $request->string('title'),
                (string) $request->string('category'),
                $request->string('icon'),
                (string) $request->string('summary'),
                (string) $request->string('description'),
                $request->string('owner_id'),
            ));
            return Response::json(['data' => $out->toArray()], 201);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'errors' => $e->fields()], 422);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function updateProjectAdmin(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        try {
            $sortOrderRaw = $request->input('sort_order');
            $out = $this->updateProject->execute(new AdminUpdateProjectInput(
                $acting, $id,
                $request->string('title'),
                $request->string('category'),
                $request->string('icon'),
                $request->string('summary'),
                $request->string('description'),
                is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : null,
            ));
            return Response::json(['data' => $out->toArray()]);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'errors' => $e->fields()], 422);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function changeProjectStatus(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        $status = (string) $request->string('status');
        try {
            $this->changeProjectStatus->execute(new ChangeProjectStatusInput($acting, $id, $status));
            return Response::json(['data' => ['id' => $id, 'status' => $status]]);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'errors' => $e->fields()], 422);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function setProjectFeatured(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        $featured = (bool) $request->input('featured');
        try {
            $this->setProjectFeatured->execute(new SetProjectFeaturedInput($acting, $id, $featured));
            return Response::json(['data' => ['id' => $id, 'featured' => $featured]]);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function approveProjectProposal(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        try {
            $out = $this->approveProposal->execute(new ApproveProjectProposalInput(
                $acting, $id, $request->string('note'),
            ));
            return Response::json(['data' => $out->toArray()], 201);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'errors' => $e->fields()], 422);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function rejectProjectProposal(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $id = (string) ($params['id'] ?? '');
        try {
            $this->rejectProposal->execute(new RejectProjectProposalInput(
                $acting, $id, $request->string('note'),
            ));
            return Response::json(null, 204);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'errors' => $e->fields()], 422);
        }
    }

    public function listProjectComments(Request $request): Response
    {
        $acting = $request->requireActingUser();
        $limitRaw = $request->query('limit');
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : null;
        try {
            $out = $this->listProjectComments->execute(new ListProjectCommentsForAdminInput($acting, $limit));
            return Response::json($out->toArray());
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        }
    }

    /**
     * @param array<string,string> $params
     */
    public function deleteProjectComment(Request $request, array $params): Response
    {
        $acting = $request->requireActingUser();
        $projectId = (string) ($params['id'] ?? '');
        $commentId = (string) ($params['comment_id'] ?? '');
        try {
            $this->deleteProjectComment->execute(new DeleteProjectCommentAsAdminInput(
                $acting, $projectId, $commentId, $request->string('reason'),
            ));
            return Response::json(null, 204);
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        }
    }

    public function statsProjects(Request $request): Response
    {
        $acting = $request->requireActingUser();
        $tenant = $this->requireTenant($request);

        try {
            $out = $this->listProjectsStats->execute(new ListProjectsStatsInput(
                acting:   $acting,
                tenantId: $tenant->id,
            ));
        } catch (ForbiddenException) {
            return Response::forbidden('Admin only');
        }

        return Response::json(['data' => $out->stats]);
    }

    /** @param array<string, string> $params */
    public function getProjectWithTranslations(Request $request, array $params): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $acting = $request->requireActingUser();
        $projectId = (string) ($params['id'] ?? '');

        try {
            $out = $this->getProjectWithAllTranslations->execute(
                new GetProjectWithAllTranslationsInput($tenantId, $projectId, $acting),
            );
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        }
        return Response::json(['data' => $out->project]);
    }

    /** @param array<string, string> $params */
    public function updateProjectTranslation(Request $request, array $params): Response
    {
        $tenantId = $this->requireTenant($request)->id;
        $acting = $request->requireActingUser();
        $projectId = (string) ($params['id'] ?? '');
        $localeRaw = (string) ($params['locale'] ?? '');
        $body = $request->all();

        try {
            $out = $this->updateProjectTranslation->execute(
                new UpdateProjectTranslationInput($tenantId, $projectId, $localeRaw, $body, $acting),
            );
        } catch (ForbiddenException) {
            return Response::json(['error' => 'forbidden'], 403);
        } catch (InvalidLocaleException) {
            return Response::json(['error' => 'invalid_locale'], 400);
        } catch (NotFoundException) {
            return Response::json(['error' => 'not_found'], 404);
        } catch (\DomainException $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
        return Response::json(['data' => ['coverage' => $out->coverage]]);
    }

    private function requireTenant(Request $request): Tenant
    {
        $tenant = $request->attribute('tenant');
        if (!$tenant instanceof Tenant) {
            throw new NotFoundException('unknown_tenant');
        }
        return $tenant;
    }
}

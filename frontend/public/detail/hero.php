<?php
$categoryLabels = [
    'community'  => 'Community',
    'technology' => 'Technology',
    'events'     => 'Events',
    'research'   => 'Research',
];
$categoryLabel = $categoryLabels[$project['category']] ?? ucfirst($project['category']);

$statusLabels = ['active' => 'Active', 'planned' => 'Planned', 'archived' => 'Archived'];
$statusLabel  = $statusLabels[$project['status']] ?? ucfirst($project['status']);

$u = $_SESSION['user'] ?? null;
$adminRoles = ['global_system_administrator', 'administrator', 'system_administrator'];
$isAdmin = $u !== null && in_array(effectiveRole(), $adminRoles, true);
$isParticipant = $project['is_participant'] ?? false;
$participantCount = $project['participant_count'] ?? 0;
$title = (string) ($project['title'] ?? '');
?>
<section class="project-detail-hero project-detail-hero--<?= htmlspecialchars($project['category']) ?>">
    <div class="container project-detail-hero-content">
        <a href="/projects" class="event-detail-back">
            <i class="bi bi-arrow-left"></i> Back to Projects
        </a>
        <div class="project-detail-hero-header">
            <div class="project-detail-icon-wrap">
                <i class="bi <?= htmlspecialchars($project['icon']) ?>"></i>
            </div>
            <div class="project-detail-hero-title">
                <span class="about-tag event-detail-tag"><?= $categoryLabel ?></span>
                <h1><?= htmlspecialchars($title) ?></h1>
                <p class="event-detail-hero-meta">
                    <i class="bi bi-circle-fill project-detail-status-dot project-detail-status--<?= htmlspecialchars($project['status']) ?>"></i>
                    <?= $statusLabel ?>
                    <span class="ms-3">
                        <i class="bi bi-people me-1"></i>
                        <span id="participant-count"><?= $participantCount ?></span> participant<?= $participantCount !== 1 ? 's' : '' ?>
                    </span>
                </p>
                <div class="project-hero-actions mt-3 d-flex gap-2 flex-wrap">
                    <?php if ($u !== null && !isViewAsGuest() && $project['status'] !== 'archived'): ?>
                        <?php if ($isParticipant): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-4" id="leave-project-btn" data-slug="<?= htmlspecialchars($project['slug']) ?>">
                            <i class="bi bi-box-arrow-right me-1"></i> Leave Project
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-dark btn-sm px-4" id="join-project-btn" data-slug="<?= htmlspecialchars($project['slug']) ?>">
                            <i class="bi bi-person-plus me-1"></i> Join Project
                        </button>
                        <?php endif; ?>
                    <?php elseif ($u === null || isViewAsGuest()): ?>
                    <a href="/?signin=1" class="btn btn-outline-secondary btn-sm px-4">
                        <i class="bi bi-lock me-1"></i> Sign in to join
                    </a>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                    <a href="/projects/<?= htmlspecialchars($project['slug']) ?>/edit" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

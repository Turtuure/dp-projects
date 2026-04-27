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

$cardMods = [
    'community'  => 'project-detail-meta-card--community',
    'technology' => 'project-detail-meta-card--technology',
    'events'     => 'project-detail-meta-card--events',
    'research'   => 'project-detail-meta-card--research',
];
$cardClass = $cardMods[$project['category']] ?? 'project-detail-meta-card--community';
$listClass = str_replace('project-detail-meta-card', 'project-detail-meta-list', $cardClass);

$u = $_SESSION['user'] ?? null;
$adminRoles = ['global_system_administrator', 'administrator', 'system_administrator'];
$isAdmin = $u !== null && in_array(effectiveRole(), $adminRoles, true);

$updates  = $project['updates'] ?? [];
$comments = $project['comments'] ?? [];
$description = (string) ($project['description'] ?? '');
$summary     = (string) ($project['summary'] ?? '');
?>
<section class="project-detail-content">
    <div class="container">
        <div class="row g-3 g-md-5">

            <!-- Main column -->
            <div class="col-lg-8">

                <!-- Description -->
                <div class="insight-article-body mb-5">
                    <?= $description ?>
                </div>

                <!-- Project Updates -->
                <?php if (!empty($updates) || $isAdmin): ?>
                <div class="project-updates-section mb-5">
                    <h3 class="project-section-title">
                        <i class="bi bi-megaphone me-2"></i>Updates
                    </h3>

                    <?php if (empty($updates)): ?>
                    <p class="text-muted" style="font-size:.95rem;">No updates yet.</p>
                    <?php else: ?>
                    <div class="project-updates-list">
                        <?php foreach ($updates as $upd): ?>
                        <div class="project-update-card">
                            <div class="project-update-header">
                                <strong><?= htmlspecialchars($upd['title']) ?></strong>
                                <span class="project-update-meta">
                                    <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($upd['created_at']) ?>
                                    — <?= htmlspecialchars($upd['author']) ?>
                                </span>
                            </div>
                            <div class="project-update-body">
                                <?= nl2br(htmlspecialchars($upd['content'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($isAdmin): ?>
                    <div class="project-update-form mt-3">
                        <h5>Post Update</h5>
                        <div class="project-form-error d-none" id="update-form-error" role="alert"></div>
                        <div class="forum-reply-card">
                            <div class="mb-2">
                                <input type="text" id="update-title" class="form-control" placeholder="Update title" />
                            </div>
                            <div class="mb-3">
                                <textarea id="update-content" class="form-control" rows="3" placeholder="What's new?"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-dark btn-sm px-4" id="post-update-btn"
                                    data-slug="<?= htmlspecialchars($project['slug']) ?>">
                                    <i class="bi bi-megaphone me-1"></i> Post Update
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Comments -->
                <div class="project-comments-section">
                    <h3 class="project-section-title">
                        <i class="bi bi-chat-dots me-2"></i>Discussion
                        <span class="project-comment-count ms-2"><?= count($comments) ?></span>
                    </h3>

                    <div id="project-comments-list">
                        <?php foreach ($comments as $i => $c): ?>
                        <div class="project-comment" id="comment-<?= $i + 1 ?>">
                            <div class="project-comment-avatar"
                                style="background:<?= htmlspecialchars($c['avatar_color'] ?: '#6b7280') ?>">
                                <?= htmlspecialchars($c['avatar_initials']) ?>
                            </div>
                            <div class="project-comment-body">
                                <div class="project-comment-header">
                                    <strong><?= htmlspecialchars($c['author']) ?></strong>
                                    <span class="project-comment-time"><?= htmlspecialchars($c['timestamp']) ?></span>
                                </div>
                                <div class="project-comment-content">
                                    <?= nl2br(htmlspecialchars($c['content'])) ?>
                                </div>
                                <div class="project-comment-footer">
                                    <button type="button"
                                        class="project-comment-like-btn"
                                        data-comment-id="<?= htmlspecialchars($c['id']) ?>"
                                        aria-label="Like comment">
                                        <i class="bi bi-heart"></i>
                                        <span class="project-like-count"><?= $c['likes'] ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Comment form -->
                    <div class="project-comment-form mt-4" id="project-comment-section">
                        <?php if ($u !== null && !isViewAsGuest()): ?>
                        <div class="project-form-error d-none" id="comment-form-error" role="alert"></div>
                        <div class="forum-reply-card">
                            <div class="mb-3">
                                <label for="comment-body" class="form-label">Leave a comment</label>
                                <textarea id="comment-body" class="form-control" rows="4" placeholder="Share your thoughts on this project…"></textarea>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-dark btn-sm px-4" id="submit-comment-btn"
                                    data-slug="<?= htmlspecialchars($project['slug']) ?>">
                                    <i class="bi bi-send me-1"></i> Post Comment
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="forum-reply-card text-center py-3">
                            <p class="mb-3 text-muted" style="font-size:.95rem;">Sign in to join the discussion.</p>
                            <a href="/?signin=1" class="btn btn-dark btn-sm px-4">Sign In</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="project-detail-meta-card <?= $cardClass ?>">
                    <h4>About this project</h4>
                    <ul class="project-detail-meta-list <?= $listClass ?>">
                        <li>
                            <i class="bi bi-tag"></i>
                            <div>
                                <div class="insight-meta-label">Category</div>
                                <div><?= $categoryLabel ?></div>
                            </div>
                        </li>
                        <li>
                            <i class="bi bi-circle-fill project-detail-status-dot project-detail-status--<?= htmlspecialchars($project['status']) ?>"></i>
                            <div>
                                <div class="insight-meta-label">Status</div>
                                <div><?= $statusLabel ?></div>
                            </div>
                        </li>
                        <li>
                            <i class="bi bi-people"></i>
                            <div>
                                <div class="insight-meta-label">Participants</div>
                                <div id="sidebar-participant-count"><?= $project['participant_count'] ?? 0 ?></div>
                            </div>
                        </li>
                        <li>
                            <i class="bi <?= htmlspecialchars($project['icon']) ?>"></i>
                            <div>
                                <div class="insight-meta-label">Summary</div>
                                <div><?= htmlspecialchars($summary) ?></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

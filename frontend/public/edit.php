<?php
$u = $_SESSION['user'] ?? null;
$adminRoles = ['global_system_administrator', 'administrator', 'system_administrator'];
if ($u === null || !in_array($u['role'], $adminRoles, true)) {
    header('Location: /projects/' . rawurlencode($projectSlug));
    exit;
}
// $project is already loaded by index.php/detail route
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Edit — <?= htmlspecialchars($project['title']) ?> — Daem Society</title>

        <link rel="shortcut icon" href="/assets/img/brand/daems-favicon.svg" />

        <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/assets/css/bootstrap-icons.min.css" />
        <link rel="stylesheet" href="/assets/css/daems.css" />
        <link rel="stylesheet" href="/assets/css/daems-search.css" />
    </head>
    <body>

        <?php include DAEMS_SITE_PUBLIC . '/partials/top-nav.php'; ?>

        <main class="forum-subpage">
            <div class="container" style="max-width:720px">

                <div class="project-form-header">
                    <a href="/projects/<?= htmlspecialchars($project['slug']) ?>" class="event-detail-back">
                        <i class="bi bi-arrow-left"></i> Back to Project
                    </a>
                    <h1>Edit Project</h1>
                </div>

                <div class="project-form-error d-none" id="project-form-error" role="alert"></div>

                <div class="forum-reply-card">
                    <form id="edit-project-form" data-slug="<?= htmlspecialchars($project['slug']) ?>" autocomplete="off">

                        <div class="mb-3">
                            <label for="pf-title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" id="pf-title" name="title" class="form-control" required value="<?= htmlspecialchars($project['title']) ?>" />
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="pf-category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select id="pf-category" name="category" class="form-select" required>
                                    <option value="community"  <?= $project['category'] === 'community'  ? 'selected' : '' ?>>Community</option>
                                    <option value="technology" <?= $project['category'] === 'technology' ? 'selected' : '' ?>>Technology</option>
                                    <option value="events"     <?= $project['category'] === 'events'     ? 'selected' : '' ?>>Events</option>
                                    <option value="research"   <?= $project['category'] === 'research'   ? 'selected' : '' ?>>Research</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="pf-status" class="form-label">Status</label>
                                <select id="pf-status" name="status" class="form-select">
                                    <option value="active"   <?= $project['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                                    <option value="planned"  <?= $project['status'] === 'planned'  ? 'selected' : '' ?>>Planned</option>
                                    <option value="archived" <?= $project['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pf-icon" class="form-label">Icon class</label>
                            <input type="text" id="pf-icon" name="icon" class="form-control" value="<?= htmlspecialchars($project['icon']) ?>" />
                        </div>

                        <div class="mb-3">
                            <label for="pf-summary" class="form-label">Summary <span class="text-danger">*</span></label>
                            <input type="text" id="pf-summary" name="summary" class="form-control" required value="<?= htmlspecialchars($project['summary']) ?>" maxlength="200" />
                        </div>

                        <div class="mb-4">
                            <label for="pf-description" class="form-label">Description</label>
                            <textarea id="pf-description" name="description" class="form-control" rows="8"><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-danger px-4" id="archive-project-btn" data-slug="<?= htmlspecialchars($project['slug']) ?>">
                                <i class="bi bi-archive me-1"></i> Archive Project
                            </button>
                            <div class="d-flex gap-2">
                                <a href="/projects/<?= htmlspecialchars($project['slug']) ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                                <button type="submit" class="btn btn-dark px-4">
                                    <i class="bi bi-check-circle me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </main>

        <?php include DAEMS_SITE_PUBLIC . '/partials/footer.php'; ?>

        <script src="/assets/js/bootstrap.bundle.min.js"></script>
        <script src="/assets/js/daems.js"></script>
        <script src="/assets/js/daems-search.js"></script>
    </body>
</html>

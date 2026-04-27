<?php
$u = $_SESSION['user'] ?? null;
$adminRoles = ['global_system_administrator', 'administrator', 'system_administrator'];
if ($u === null || !in_array($u['role'], $adminRoles, true)) {
    header('Location: /projects');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>New Project — Daem Society</title>

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
                    <a href="/projects" class="event-detail-back">
                        <i class="bi bi-arrow-left"></i> Back to Projects
                    </a>
                    <h1>New Project</h1>
                </div>

                <div class="project-form-error d-none" id="project-form-error" role="alert"></div>

                <div class="forum-reply-card">
                    <form id="new-project-form" autocomplete="off">

                        <div class="mb-3">
                            <label for="pf-title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" id="pf-title" name="title" class="form-control" required placeholder="Project title" />
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="pf-category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select id="pf-category" name="category" class="form-select" required>
                                    <option value="">Select…</option>
                                    <option value="community">Community</option>
                                    <option value="technology">Technology</option>
                                    <option value="events">Events</option>
                                    <option value="research">Research</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="pf-status" class="form-label">Status</label>
                                <select id="pf-status" name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="planned">Planned</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pf-icon" class="form-label">Icon class <span class="text-muted" style="font-size:.85em;">(Bootstrap Icons)</span></label>
                            <input type="text" id="pf-icon" name="icon" class="form-control" value="bi-folder" placeholder="e.g. bi-people" />
                        </div>

                        <div class="mb-3">
                            <label for="pf-summary" class="form-label">Summary <span class="text-danger">*</span></label>
                            <input type="text" id="pf-summary" name="summary" class="form-control" required placeholder="One-line description" maxlength="200" />
                        </div>

                        <div class="mb-4">
                            <label for="pf-description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea id="pf-description" name="description" class="form-control" rows="8" required placeholder="Full description (HTML allowed)"></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/projects" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-dark px-4">
                                <i class="bi bi-plus-circle me-1"></i> Create Project
                            </button>
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

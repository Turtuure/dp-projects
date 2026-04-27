<?php
$projects = ApiClient::get('/projects') ?? [];

$categoryLabels = [
    'community'  => 'Community',
    'technology' => 'Technology',
    'events'     => 'Events',
    'research'   => 'Research',
];

$u = $_SESSION['user'] ?? null;
$adminRoles = ['global_system_administrator', 'administrator', 'system_administrator'];
$isAdmin = $u !== null && in_array(effectiveRole(), $adminRoles, true);
?>
<section class="projects-grid">
    <div class="container">

        <div class="project-controls">
            <div class="project-filters">
                <button class="project-filter-btn active" data-filter="all">All</button>
                <button class="project-filter-btn" data-filter="community">Community</button>
                <button class="project-filter-btn" data-filter="technology">Technology</button>
                <button class="project-filter-btn" data-filter="events">Events</button>
                <button class="project-filter-btn" data-filter="research">Research</button>
            </div>
            <div class="project-search-wrap">
                <i class="bi bi-search project-search-icon"></i>
                <input
                    type="search"
                    id="project-search"
                    class="project-search-input"
                    placeholder="Search projects…"
                    aria-label="Search projects"
                />
            </div>
        </div>

        <div class="row g-4" id="projects-list">

            <?php foreach ($projects as $project):
                $title   = (string) ($project['title'] ?? '');
                $summary = (string) ($project['summary'] ?? '');
            ?>
            <div
                class="col-md-6 col-lg-4 project-card-wrap"
                data-category="<?= htmlspecialchars($project['category']) ?>"
                data-title="<?= htmlspecialchars(strtolower($title)) ?>"
                data-summary="<?= htmlspecialchars(strtolower($summary)) ?>"
            >
                <a href="/projects/<?= htmlspecialchars($project['slug']) ?>" class="project-card">
                    <div class="project-icon"><i class="bi <?= htmlspecialchars($project['icon']) ?>"></i></div>
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <span class="project-category"><?= htmlspecialchars($categoryLabels[$project['category']] ?? ucfirst($project['category'])) ?></span>
                        <span class="project-status-badge project-status-badge--<?= htmlspecialchars($project['status']) ?>">
                            <?= ucfirst($project['status']) ?>
                        </span>
                    </div>
                    <h3>
                        <?= htmlspecialchars($title) ?>
                        <?php if (!empty($project['featured'])): ?>
                            <span class="badge badge--featured">Featured</span>
                        <?php endif; ?>
                    </h3>
                    <p><?= htmlspecialchars($summary) ?></p>
                    <span class="project-link">View details <i class="bi bi-arrow-right"></i></span>
                </a>
            </div>
            <?php endforeach; ?>

        </div>

        <p class="project-no-results d-none" id="project-no-results">No projects match your search.</p>


    </div>
</section>

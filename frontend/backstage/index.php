<?php
/**
 * Backstage Projects Admin
 *
 * Three tabs:
 *   - proposals: review pending project proposals, approve/reject.
 *   - projects : list + CRUD + status + featured toggle.
 *   - comments : moderation of recent comments across all projects.
 */

declare(strict_types=1);

if (!class_exists('ApiClient')) {
    require_once DAEMS_SITE_PUBLIC . '/../src/ApiClient.php';
}

$pageTitle   = 'Projects';
$activePage  = 'projects';
$breadcrumbs = [];

$activeTab = $_GET['tab'] ?? 'proposals';
if (!in_array($activeTab, ['proposals', 'projects', 'comments'], true)) {
    $activeTab = 'proposals';
}

/**
 * Fetch {items, total} from backend via raw cURL — these backstage list endpoints
 * return the envelope directly without a 'data' wrapper, so ApiClient::get would
 * return null.
 */
$fetchList = static function (string $path): array {
    $token   = (string) ($_SESSION['token'] ?? '');
    $headers = ['Accept: application/json', 'Host: daems-platform.local'];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $ch = curl_init('http://daems-platform.local/api/v1' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['items'])) {
            return $decoded;
        }
    }
    return ['items' => [], 'total' => 0];
};

$proposals = $fetchList('/backstage/proposals');
$projects  = $fetchList('/backstage/projects');
$comments  = $fetchList('/backstage/comments/recent');

// Pending Project Proposals count for the sub-page card.
$projectProposalsPending = 0;
foreach (($proposals['items'] ?? []) as $item) {
    if (is_array($item) && isset($item['status']) && $item['status'] === 'pending') {
        $projectProposalsPending++;
    }
}

$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

$categoryLabels = [
    'community'  => 'Community',
    'technology' => 'Technology',
    'events'     => 'Events',
    'research'   => 'Research',
];

ob_start();
?>
<div class="projects-admin">

<div class="page-header">
    <div>
        <h1 class="page-header__title">Projects</h1>
        <p class="page-header__subtitle">Moderate proposals, manage projects, and keep comments clean.</p>
    </div>
    <div>
        <a href="/backstage/projects/new" class="btn btn--primary" id="btn-new-project">+ New project</a>
    </div>
</div>

<?php
$icon_check    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 6L9 17l-5-5"/></svg>';
$icon_pencil   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
$icon_star     = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
$icon_inbox    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>';

$kpis = [
    ['kpi_id' => 'active',            'label' => 'Active',             'value' => '—', 'icon_html' => $icon_check,  'icon_variant' => 'green',  'trend_label' => 'in progress',     'trend_direction' => 'muted'],
    ['kpi_id' => 'drafts',            'label' => 'Drafts',             'value' => '—', 'icon_html' => $icon_pencil, 'icon_variant' => 'gray',   'trend_label' => 'unpublished',     'trend_direction' => 'muted'],
    ['kpi_id' => 'featured',          'label' => 'Featured',           'value' => '—', 'icon_html' => $icon_star,   'icon_variant' => 'purple', 'trend_label' => 'curated',         'trend_direction' => 'muted'],
    ['kpi_id' => 'pending_proposals', 'label' => 'Pending proposals',  'value' => '—', 'icon_html' => $icon_inbox,  'icon_variant' => 'amber',  'trend_label' => 'awaiting review', 'trend_direction' => 'warn'],
];
?>
<div class="kpis-grid">
  <?php foreach ($kpis as $kpi): daems_shared_partial('components/cards/kpi-card/kpi-card', $kpi); endforeach; ?>
</div>
<script src="/modules/projects/assets/backstage/projects-stats.js" defer></script>

<?php
    $cardTitle        = 'Project Proposals';
    $cardHref         = '/backstage/project-proposals';
    $cardPendingCount = $projectProposalsPending;
    $cardSubtitle     = 'Member-submitted projects awaiting review';
    include DAEMS_SITE_PUBLIC . '/pages/backstage/partials/sub-page-card.php';
?>

<!-- Tabs -->
<div class="proj-tabs" role="tablist">
    <button type="button" class="proj-tab <?= $activeTab === 'proposals' ? 'is-active' : '' ?>"
            data-tab="proposals" role="tab" aria-selected="<?= $activeTab === 'proposals' ? 'true' : 'false' ?>">
        Proposals
        <?php if (!empty($proposals['items'])): ?>
            <span class="proj-tab__badge"><?= (int) ($proposals['total'] ?? count($proposals['items'])) ?></span>
        <?php endif; ?>
    </button>
    <button type="button" class="proj-tab <?= $activeTab === 'projects' ? 'is-active' : '' ?>"
            data-tab="projects" role="tab" aria-selected="<?= $activeTab === 'projects' ? 'true' : 'false' ?>">
        Projects
        <span class="proj-tab__badge proj-tab__badge--muted"><?= (int) ($projects['total'] ?? count($projects['items'])) ?></span>
    </button>
    <button type="button" class="proj-tab <?= $activeTab === 'comments' ? 'is-active' : '' ?>"
            data-tab="comments" role="tab" aria-selected="<?= $activeTab === 'comments' ? 'true' : 'false' ?>">
        Comments
        <span class="proj-tab__badge proj-tab__badge--muted"><?= (int) ($comments['total'] ?? count($comments['items'])) ?></span>
    </button>
</div>

<!-- Proposals tab -->
<section class="proj-tab-content <?= $activeTab === 'proposals' ? 'is-active' : '' ?>" data-tab-content="proposals">
    <?php if (empty($proposals['items'])): ?>
        <div class="card"><div class="card__body proj-empty">No pending project proposals.</div></div>
    <?php else: ?>
        <?php foreach ($proposals['items'] as $p): ?>
            <article id="app-<?= $esc((string) ($p['id'] ?? '')) ?>" class="card proj-proposal-card">
                <div class="card__body">
                    <div class="proj-proposal-head">
                        <div>
                            <h3 class="proj-proposal-title"><?= $esc((string) ($p['title'] ?? '')) ?></h3>
                            <div class="proj-proposal-meta">
                                <span><strong><?= $esc((string) ($p['author_name'] ?? '')) ?></strong></span>
                                <span><?= $esc((string) ($p['author_email'] ?? '')) ?></span>
                                <span class="proj-category-pill">
                                    <?= $esc($categoryLabels[$p['category'] ?? ''] ?? ucfirst((string) ($p['category'] ?? ''))) ?>
                                </span>
                            </div>
                        </div>
                        <div class="proj-proposal-date">
                            <?= $esc(substr((string) ($p['created_at'] ?? ''), 0, 10)) ?>
                        </div>
                    </div>

                    <p class="proj-proposal-summary"><?= $esc((string) ($p['summary'] ?? '')) ?></p>

                    <?php if (!empty($p['description'])): ?>
                        <details class="proj-proposal-details">
                            <summary>Read full description</summary>
                            <p><?= nl2br($esc((string) $p['description'])) ?></p>
                        </details>
                    <?php endif; ?>

                    <div class="proj-proposal-actions">
                        <textarea class="proj-note" data-proposal-note="<?= $esc((string) $p['id']) ?>"
                                  rows="2" placeholder="Optional note to applicant…"></textarea>
                        <button type="button" class="btn btn--primary btn--sm" data-proposal-approve="<?= $esc((string) $p['id']) ?>">Approve</button>
                        <button type="button" class="btn btn--ghost btn--sm" data-proposal-reject="<?= $esc((string) $p['id']) ?>">Reject</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Projects tab -->
<section class="proj-tab-content <?= $activeTab === 'projects' ? 'is-active' : '' ?>" data-tab-content="projects">
    <div class="card proj-filters-card">
        <div class="card__body">
            <div class="proj-filters-row">
                <label class="proj-field">
                    <span class="proj-label">Status</span>
                    <select id="proj-filter-status">
                        <option value="">All statuses</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>
                <label class="proj-field">
                    <span class="proj-label">Category</span>
                    <select id="proj-filter-category">
                        <option value="">All categories</option>
                        <option value="community">Community</option>
                        <option value="technology">Technology</option>
                        <option value="events">Events</option>
                        <option value="research">Research</option>
                    </select>
                </label>
                <label class="proj-field proj-field--search">
                    <span class="proj-label">Search</span>
                    <input type="text" id="proj-filter-search" placeholder="Title&hellip;">
                </label>
            </div>
        </div>
    </div>

    <div class="card proj-table-card">
        <div class="card__body proj-table-body">
            <div class="proj-meta-row"><strong id="proj-count">Loading&hellip;</strong></div>
            <table class="data-table proj-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Translations</th>
                        <th>Members</th>
                        <th>Comments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="proj-tbody">
                    <tr><td colspan="8" class="proj-empty">Loading&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Comments tab -->
<section class="proj-tab-content <?= $activeTab === 'comments' ? 'is-active' : '' ?>" data-tab-content="comments">
    <div class="card proj-filters-card">
        <div class="card__body">
            <div class="proj-filters-row">
                <label class="proj-field">
                    <span class="proj-label">Project</span>
                    <select id="proj-comment-filter-project">
                        <option value="">All projects</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div class="card proj-table-card">
        <div class="card__body proj-table-body">
            <div class="proj-meta-row"><strong id="proj-comment-count">Loading&hellip;</strong></div>
            <table class="data-table proj-comments-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Project</th>
                        <th>Author</th>
                        <th>Content</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="proj-comments-tbody">
                    <tr><td colspan="5" class="proj-empty">Loading&hellip;</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

</div><!-- /.projects-admin -->

<!-- Confirmation dialogs (proposal approve/reject, comment delete, status change) reuse the
     events-module modal tokens for visual consistency. -->
<link rel="stylesheet" href="/pages/backstage/events/event-modal.css">
<link rel="stylesheet" href="/modules/projects/assets/backstage/projects-admin.css">
<link rel="stylesheet" href="/pages/backstage/shared/sub-page-card.css">
<script>
window.DAEMS_PROJECTS_TAB = <?= json_encode([
    'proposals' => $proposals,
    'projects'  => $projects,
    'comments'  => $comments,
    'activeTab' => $activeTab,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/modules/projects/assets/backstage/projects-admin.js"></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/backstage/layout.php';

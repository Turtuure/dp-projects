<?php
/**
 * Backstage Projects Admin
 *
 * Three KPI card-tabs (Projects / Proposals / Comments) drive the panels below.
 * Clicking a card swaps the visible panel — no separate tab nav row.
 *   - projects : list + CRUD + status + featured toggle.
 *   - proposals: review pending project proposals, approve/reject.
 *   - comments : moderation of recent comments across all projects.
 */

declare(strict_types=1);

use Daems\Frontend\ApiClient;

$pageTitle   = 'backstage.title.projects';
$activePage  = 'projects';
$breadcrumbs = [];

$activeTab = $_GET['tab'] ?? 'projects';
if (!in_array($activeTab, ['proposals', 'projects', 'comments'], true)) {
    $activeTab = 'projects';
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

// Pending Project Proposals count — drives the Proposals card-tab value.
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
// SVG icons for the card-tabs.
$icon_folder  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
$icon_inbox   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>';
$icon_chat    = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';

// Server-rendered placeholders. projects-stats.js refines the Projects card
// subtitle ("X drafts • Y featured") once the stats endpoint responds.
$projectsTotal    = (int) ($projects['total'] ?? count($projects['items'] ?? []));
$commentsTotal    = (int) ($comments['total'] ?? count($comments['items'] ?? []));

$cardTabs = [
    [
        'tab'         => 'projects',
        'label'       => 'Projects',
        'value'       => $projectsTotal,
        'subtitle'    => 'all statuses',
        'icon_html'   => $icon_folder,
        'icon_variant'=> 'blue',
    ],
    [
        'tab'         => 'proposals',
        'label'       => 'Proposals',
        'value'       => $projectProposalsPending,
        'subtitle'    => 'awaiting review',
        'icon_html'   => $icon_inbox,
        'icon_variant'=> 'amber',
    ],
    [
        'tab'         => 'comments',
        'label'       => 'Comments',
        'value'       => $commentsTotal,
        'subtitle'    => 'across all projects',
        'icon_html'   => $icon_chat,
        'icon_variant'=> 'purple',
    ],
];
?>
<div class="kpis-grid kpis-grid--tabs" role="tablist" aria-label="Projects sections">
  <?php foreach ($cardTabs as $t): $isActive = $activeTab === $t['tab']; ?>
    <button type="button"
            class="kpi-card kpi-card--tab<?= $isActive ? ' is-active' : '' ?>"
            role="tab"
            id="proj-tab-<?= $esc($t['tab']) ?>"
            data-tab="<?= $esc($t['tab']) ?>"
            data-kpi="<?= $esc($t['tab']) ?>"
            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
            aria-controls="proj-panel-<?= $esc($t['tab']) ?>">
      <div class="kpi-card__head">
        <div>
          <div class="kpi-card__label"><?= $esc($t['label']) ?></div>
          <div class="kpi-card__value"><?= $esc((string) $t['value']) ?></div>
          <div class="kpi-card__trend kpi-card__trend--muted" data-tab-subtitle="<?= $esc($t['tab']) ?>">
            <?= $esc($t['subtitle']) ?>
          </div>
        </div>
        <span class="kpi-card__icon kpi-card__icon--<?= $esc($t['icon_variant']) ?>"><?= $t['icon_html'] /* trusted inline SVG */ ?></span>
      </div>
    </button>
  <?php endforeach; ?>
</div>
<script src="/modules/projects/assets/backstage/projects-stats.js" defer></script>

<!-- Proposals panel -->
<section class="proj-tab-content <?= $activeTab === 'proposals' ? 'is-active' : '' ?>"
         data-tab-content="proposals" id="proj-panel-proposals"
         role="tabpanel" aria-labelledby="proj-tab-proposals">
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

<!-- Projects panel -->
<section class="proj-tab-content <?= $activeTab === 'projects' ? 'is-active' : '' ?>"
         data-tab-content="projects" id="proj-panel-projects"
         role="tabpanel" aria-labelledby="proj-tab-projects">
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

<!-- Comments panel -->
<section class="proj-tab-content <?= $activeTab === 'comments' ? 'is-active' : '' ?>"
         data-tab-content="comments" id="proj-panel-comments"
         role="tabpanel" aria-labelledby="proj-tab-comments">
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
<link rel="stylesheet" href="/modules/events/assets/backstage/event-modal.css">
<link rel="stylesheet" href="/modules/projects/assets/backstage/projects-admin.css">
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
require DAEMS_SITE_PUBLIC . '/pages/layout.php';

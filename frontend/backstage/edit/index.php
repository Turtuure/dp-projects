<?php
declare(strict_types=1);

use Daems\Frontend\ApiClient;

$u = $_SESSION['user'] ?? null;
$isAdmin = $u && (!empty($u['is_platform_admin']) || ($u['role'] ?? '') === 'admin'
               || ($u['role'] ?? '') === 'global_system_administrator');
if (!$isAdmin) { header('Location: /'); exit; }

$id = trim((string) ($_GET['id'] ?? ''));
if ($id === '') {
    header('Location: /backstage/projects?tab=projects');
    exit;
}

// Server-side fetch so the form pre-fills synchronously (no flash of empty fields).
// GET /backstage/projects/{id}/translations returns chrome + per-locale translations + coverage.
$project = ApiClient::get('/backstage/projects/' . rawurlencode($id) . '/translations');
if (!is_array($project) || empty($project)) {
    http_response_code(404);
    http_response_code(404); echo '<h1>Not found</h1>'; exit;
    exit;
}

$translations = is_array($project['translations'] ?? null) ? $project['translations'] : [];
$coverage     = is_array($project['coverage']     ?? null) ? $project['coverage']     : [];

// Title for the page subheader: prefer fi_FI, then en_GB, then sw_TZ.
$displayTitle = '(untitled)';
foreach (['fi_FI', 'en_GB', 'sw_TZ'] as $loc) {
    if (!empty($translations[$loc]['title'])) {
        $displayTitle = (string) $translations[$loc]['title'];
        break;
    }
}

$pageTitle   = 'backstage.title.projects_edit';
$activePage  = 'projects';
$breadcrumbs = [
    ['label' => 'Projects', 'url' => '/backstage/projects'],
    ['label' => 'Edit'],
];

$primary_label = 'Save';
$show_delete   = true;
$contentClass  = 'content--no-scroll';

$titleSafe = htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8');
$idSafe    = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-header__title">Edit project</h1>
        <p class="page-header__subtitle"><?= $titleSafe ?></p>
    </div>
    <div>
        <a href="/backstage/projects?tab=projects" class="btn btn--outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Back to projects
        </a>
    </div>
</div>

<div class="project-form-panel project-form-panel--full-height" data-mode="edit" data-project-id="<?= $idSafe ?>">
    <?php include __DIR__ . '/../_form.php'; ?>
</div>

<link rel="stylesheet" href="/backstage/pages/shared/locale-cards.css">
<link rel="stylesheet" href="/modules/projects/assets/backstage/project-form.css">
<script src="/backstage/pages/shared/locale-cards.js" defer></script>
<script src="/modules/projects/assets/backstage/project-form-page.js" defer></script>

<?php
$pageContent = ob_get_clean();
require DAEMS_SITE_PUBLIC . '/pages/layout.php';

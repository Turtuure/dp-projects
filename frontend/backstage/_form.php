<?php
/**
 * Shared project create/edit form.
 *
 * Renders a 2-column layout:
 *   LEFT  — translation locale-cards grid (3 locales × title/summary/description)
 *           + per-locale fields. Save is per-locale via locale-cards component.
 *   RIGHT — chrome metadata: status, featured, category, icon, slug, sort order.
 *           Action buttons (Save, Publish/Archive, Delete) sit at the bottom.
 *
 * Variables expected before include:
 * @var array  $project        Pre-fill values, or [] for an empty form.
 *                              Keys: id, slug, category, icon, status, sort_order, featured.
 * @var array  $translations   Per-locale translations map (e.g. ['fi_FI' => ['title' => ..., 'summary' => ..., 'description' => ...]]).
 *                              Empty array on create; populated on edit.
 * @var array  $coverage       Per-locale coverage map (e.g. ['fi_FI' => ['filled' => 3, 'total' => 3]]).
 * @var string $primary_label  Submit button label ('Create' for new, 'Save' for edit).
 * @var bool   $show_delete    If true, render the Archive button (project archive flow).
 */
declare(strict_types=1);

$project       = $project       ?? [];
$translations  = $translations  ?? [];
$coverage      = $coverage      ?? [];
$primary_label = $primary_label ?? 'Create';
$show_delete   = $show_delete   ?? false;

$v = static function (string $key) use ($project): string {
    $val = $project[$key] ?? '';
    return is_string($val) ? $val : (string) $val;
};
$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$projectId  = (string) ($project['id'] ?? '');
$status     = $v('status') !== '' ? $v('status') : 'draft';
$category   = $v('category');
$featured   = !empty($project['featured']);
$sortOrder  = isset($project['sort_order']) ? (int) $project['sort_order'] : 0;
?>
<div class="project-form" id="project-form">
    <div class="project-form__cols">

        <!-- LEFT COLUMN — Translations (locale-cards) -->
        <div class="project-form__col project-form__col--left">

            <div class="project-form__field project-form__field--grow">
                <span class="project-form__label">
                    Translations
                    <span class="project-form__hint">title · summary · description per locale</span>
                </span>

                <div class="locale-cards-container project-form__locales"
                     data-kind="project"
                     data-entity-id="<?= $esc($projectId) ?>">
                    <div class="locale-cards-grid" role="tablist" aria-label="Locale translations"></div>
                    <div class="locale-cards-editor">
                        <div class="locale-cards-fields"></div>
                        <div class="locale-cards-actions">
                            <button type="button" class="btn btn--outline locale-cards-save">Save</button>
                            <span class="locale-cards-status" aria-live="polite"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN — chrome metadata + actions -->
        <div class="project-form__col project-form__col--right">

            <div class="project-form__col-scroll">

                <div class="project-form__field">
                    <label class="project-form__label" for="pf-status">Status</label>
                    <select id="pf-status" name="status" class="project-form__input">
                        <?php foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $val => $lbl): ?>
                            <option value="<?= $esc($val) ?>"<?= $val === $status ? ' selected' : '' ?>><?= $esc($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="project-form__field">
                    <label class="project-form__label project-form__label--required" for="pf-category">Category</label>
                    <select id="pf-category" name="category" class="project-form__input" required>
                        <option value="">— select —</option>
                        <?php foreach (['community' => 'Community', 'technology' => 'Technology', 'events' => 'Events', 'research' => 'Research'] as $val => $lbl): ?>
                            <option value="<?= $esc($val) ?>"<?= $val === $category ? ' selected' : '' ?>><?= $esc($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="project-form__error" id="pf-err-category"></span>
                </div>

                <div class="project-form__field">
                    <label class="project-form__label" for="pf-icon">
                        Icon
                        <span class="project-form__hint">Bootstrap Icons class</span>
                    </label>
                    <input type="text" id="pf-icon" name="icon"
                           class="project-form__input"
                           placeholder="bi-lightbulb"
                           value="<?= $esc($v('icon')) ?>">
                </div>

                <div class="project-form__field">
                    <label class="project-form__label" for="pf-slug">
                        Slug
                        <span class="project-form__hint">read-only · derived from title</span>
                    </label>
                    <input type="text" id="pf-slug" name="slug"
                           class="project-form__input"
                           value="<?= $esc($v('slug')) ?>"
                           readonly>
                </div>

                <div class="project-form__field">
                    <label class="project-form__label" for="pf-sort-order">
                        Sort order
                        <span class="project-form__hint">lower = earlier</span>
                    </label>
                    <input type="number" id="pf-sort-order" name="sort_order"
                           class="project-form__input"
                           value="<?= $sortOrder ?>"
                           min="0" step="1">
                </div>

                <div class="project-form__field">
                    <span class="project-form__label">Featured</span>
                    <label class="toggle-switch" for="pf-featured">
                        <input type="checkbox" id="pf-featured" name="featured"
                               <?= $featured ? 'checked' : '' ?>>
                        <span class="toggle-switch__track" aria-hidden="true">
                            <span class="toggle-switch__thumb"></span>
                        </span>
                        <span class="toggle-switch__label" data-on="Highlighted on the public site" data-off="Hidden from the highlighted list">
                            <?= $featured ? 'Highlighted on the public site' : 'Hidden from the highlighted list' ?>
                        </span>
                    </label>
                </div>
            </div>

            <div class="project-form__actions">
                <?php if ($show_delete): ?>
                    <button type="button" class="btn btn--danger-outline project-form__delete" id="pf-archive">Archive</button>
                <?php else: ?>
                    <a href="/backstage/projects?tab=projects" class="btn btn--danger-outline">Cancel</a>
                <?php endif; ?>
                <button type="button" class="btn btn--outline" id="pf-save"><?= $esc($primary_label) ?></button>
                <?php if ($show_delete): ?>
                    <button type="button" class="btn btn--success-outline" id="pf-publish">
                        <?= $status === 'active' ? 'Set to Draft' : 'Publish' ?>
                    </button>
                <?php endif; ?>
            </div>

            <div id="pf-error-mount" class="project-form__error-banner" style="display:none;"></div>
        </div>

    </div>
</div>

<script>
window.DAEMS_PROJECT_FORM = {
    id:           <?= json_encode($projectId, JSON_UNESCAPED_SLASHES) ?>,
    translations: <?= json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    coverage:     <?= json_encode($coverage, JSON_UNESCAPED_SLASHES) ?>
};
</script>

/**
 * Project create/edit sub-page handlers.
 *
 * Buttons:
 *   - Save     (#pf-save)    — POST chrome (category, icon, sort_order). On create
 *                              also posts the fi_FI title/summary/description seed.
 *                              In edit, also patches status/featured if they changed.
 *   - Publish  (#pf-publish) — only in edit mode: toggles status active <-> draft.
 *   - Archive  (#pf-archive) — only in edit mode: sets status to 'archived' after confirm.
 *   - Cancel   (anchor)      — only in create mode, navigates back to list.
 *
 * Translations save themselves per-locale via the locale-cards component
 * (POST /api/v1/backstage/projects/{id}/translations/{locale}).
 *
 * Reads mode + id from the parent .project-form-panel data attributes:
 *   data-mode        : 'create' | 'edit'
 *   data-project-id  : present only when mode === 'edit'
 *
 * Pre-fill values for translations + coverage are bridged through window.DAEMS_PROJECT_FORM
 * (set by _form.php from server-side state).
 */
(function () {
    'use strict';

    var panel = document.querySelector('.project-form-panel');
    if (!panel) return;

    var mode      = panel.getAttribute('data-mode') || 'create';
    var projectId = panel.getAttribute('data-project-id') || '';

    var bridge = window.DAEMS_PROJECT_FORM || { id: projectId, translations: {}, coverage: {} };

    var statusEl    = document.getElementById('pf-status');
    var categoryEl  = document.getElementById('pf-category');
    var iconEl      = document.getElementById('pf-icon');
    var slugEl      = document.getElementById('pf-slug');
    var sortOrderEl = document.getElementById('pf-sort-order');
    var featuredEl  = document.getElementById('pf-featured');
    var saveBtn     = document.getElementById('pf-save');
    var publishBtn  = document.getElementById('pf-publish');
    var archiveBtn  = document.getElementById('pf-archive');
    var errorEl     = document.getElementById('pf-error-mount');

    // Snapshot starting status/featured so edit-mode can decide whether to
    // hit the dedicated status/featured endpoints alongside the chrome POST.
    var initialStatus   = statusEl   ? statusEl.value : 'draft';
    var initialFeatured = !!(featuredEl && featuredEl.checked);

    var labels = {
        save:    saveBtn    ? saveBtn.textContent.trim()    : '',
        publish: publishBtn ? publishBtn.textContent.trim() : '',
        archive: archiveBtn ? archiveBtn.textContent.trim() : '',
    };

    // ── Featured toggle helper-label swap ───────────────────────────────
    if (featuredEl) {
        var toggleLabel = panel.querySelector('.toggle-switch__label');
        if (toggleLabel) {
            var onText  = toggleLabel.getAttribute('data-on')  || toggleLabel.textContent;
            var offText = toggleLabel.getAttribute('data-off') || toggleLabel.textContent;
            var syncLabel = function () {
                toggleLabel.textContent = featuredEl.checked ? onText : offText;
            };
            featuredEl.addEventListener('change', syncLabel);
            syncLabel();
        }
    }

    // ── Locale-cards mount ──────────────────────────────────────────────
    function mountLocaleCards() {
        var container = panel.querySelector('.locale-cards-container');
        if (!container || !window.LocaleCards) return;
        window.LocaleCards.mount(container, {
            kind:         'project',
            entityId:     bridge.id || '',
            translations: bridge.translations || {},
            coverage:     bridge.coverage || {
                fi_FI: { filled: 0, total: 3 },
                en_GB: { filled: 0, total: 3 },
                sw_TZ: { filled: 0, total: 3 }
            }
        });
    }

    if (window.LocaleCards) {
        mountLocaleCards();
    } else {
        // locale-cards.js is loaded with `defer`; if we ran before it parsed,
        // wait for window load then mount. (Both this file and locale-cards.js
        // are deferred, so the parse-order rule says locale-cards.js runs first
        // when included first — but be defensive in case of re-orders.)
        document.addEventListener('DOMContentLoaded', mountLocaleCards);
        window.addEventListener('load', mountLocaleCards);
    }

    // ── Read fi_FI draft for create-mode seed ───────────────────────────
    function readFiFIDraft() {
        var container = panel.querySelector('.locale-cards-container');
        if (!container) return { title: '', summary: '', description: '' };
        // The locale-cards component renders only the ACTIVE locale's fields
        // in the DOM. On a fresh create page the active locale defaults to
        // fi_FI, so reading the inputs gives us the seed we need.
        var draft = { title: '', summary: '', description: '' };
        container.querySelectorAll('.locale-cards-fields input, .locale-cards-fields textarea').forEach(function (i) {
            draft[i.name] = i.value;
        });
        return draft;
    }

    // ── Error helpers ──────────────────────────────────────────────────
    function showError(message) {
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.style.display = '';
        errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    function clearError() {
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    }
    function showFieldError(field, msg) {
        var el = document.getElementById('pf-err-' + field);
        if (el) el.textContent = msg || '';
        var input = document.getElementById('pf-' + field);
        if (input) {
            if (msg) input.classList.add('is-error'); else input.classList.remove('is-error');
        }
    }
    function clearFieldErrors() {
        ['category'].forEach(function (f) { showFieldError(f, ''); });
    }

    // ── Busy state ─────────────────────────────────────────────────────
    function setAllBusy(busy) {
        [saveBtn, publishBtn, archiveBtn].forEach(function (b) {
            if (b) b.disabled = busy;
        });
    }
    function restoreLabels() {
        if (saveBtn)    saveBtn.textContent    = labels.save;
        if (publishBtn) publishBtn.textContent = labels.publish;
        if (archiveBtn) archiveBtn.textContent = labels.archive;
    }

    // ── Save flow ──────────────────────────────────────────────────────
    if (saveBtn) {
        saveBtn.addEventListener('click', function () { handleSave(); });
    }
    if (publishBtn) {
        publishBtn.addEventListener('click', handlePublishToggle);
    }
    if (archiveBtn) {
        archiveBtn.addEventListener('click', handleArchive);
    }

    function handleSave() {
        clearError();
        clearFieldErrors();

        // Chrome validation
        var category = categoryEl ? categoryEl.value : '';
        if (!category) {
            showFieldError('category', 'Please select a category.');
            return;
        }

        // Create mode also needs at least the fi_FI title/summary/description seed.
        if (mode === 'create') {
            var draft = readFiFIDraft();
            if ((draft.title || '').trim().length < 3) {
                showError('Title must be at least 3 characters (enter it under the Suomi card).');
                return;
            }
            if ((draft.summary || '').trim().length < 10) {
                showError('Summary must be at least 10 characters (enter it under the Suomi card).');
                return;
            }
            if ((draft.description || '').trim().length < 20) {
                showError('Description must be at least 20 characters (enter it under the Suomi card).');
                return;
            }
        }

        setAllBusy(true);
        if (saveBtn) saveBtn.textContent = 'Saving…';

        if (mode === 'create') {
            doCreate(category);
        } else {
            doUpdate(category);
        }
    }

    function doCreate(category) {
        var draft = readFiFIDraft();
        var body = {
            category:      category,
            icon:          (iconEl && iconEl.value.trim()) || null,
            title:         (draft.title       || '').trim(),
            summary:       (draft.summary     || '').trim(),
            description:   (draft.description || '').trim(),
            source_locale: 'fi_FI'
        };
        postJson('/api/backstage/projects?op=create', body)
            .then(function (res) {
                if (!res.ok) {
                    handleSaveError(res);
                    return;
                }
                // Created — redirect to the edit page so the user can fill
                // additional locales + tweak status/featured.
                var newId = (res.data && (res.data.id || (res.data.data && res.data.data.id))) || '';
                if (newId) {
                    window.location.href = '/backstage/projects/edit?id=' + encodeURIComponent(newId);
                } else {
                    window.location.href = '/backstage/projects?tab=projects';
                }
            })
            .catch(function (e) {
                setAllBusy(false);
                restoreLabels();
                showError('Save failed: ' + (e && e.message || 'unknown error'));
            });
    }

    function doUpdate(category) {
        var body = {
            category:   category,
            icon:       (iconEl && iconEl.value.trim()) || null,
            sort_order: sortOrderEl ? parseInt(sortOrderEl.value, 10) : null
        };
        if (!isFinite(body.sort_order)) body.sort_order = null;

        // Chain: chrome update → status (if changed) → featured (if changed).
        postJson('/api/backstage/projects?op=update&id=' + encodeURIComponent(projectId), body)
            .then(function (res) {
                if (!res.ok) { handleSaveError(res); return null; }

                var nextStatus   = statusEl   ? statusEl.value         : initialStatus;
                var nextFeatured = !!(featuredEl && featuredEl.checked);

                var chain = Promise.resolve(res);
                if (nextStatus !== initialStatus) {
                    chain = chain.then(function () {
                        return postJson(
                            '/api/backstage/projects?op=status&id=' + encodeURIComponent(projectId),
                            { status: nextStatus }
                        );
                    });
                }
                if (nextFeatured !== initialFeatured) {
                    chain = chain.then(function () {
                        return postJson(
                            '/api/backstage/projects?op=featured&id=' + encodeURIComponent(projectId),
                            { featured: nextFeatured }
                        );
                    });
                }
                return chain;
            })
            .then(function (final) {
                if (!final) return; // already handled error
                if (final.ok === false) { handleSaveError(final); return; }
                window.location.href = '/backstage/projects?tab=projects';
            })
            .catch(function (e) {
                setAllBusy(false);
                restoreLabels();
                showError('Save failed: ' + (e && e.message || 'unknown error'));
            });
    }

    function handleSaveError(res) {
        setAllBusy(false);
        restoreLabels();
        var msg = (res.data && (res.data.error || res.data.message)) || 'Save failed.';
        if (res.data && res.data.errors) {
            Object.keys(res.data.errors).forEach(function (k) {
                var v = res.data.errors[k];
                showFieldError(k, Array.isArray(v) ? v.join(' ') : String(v));
            });
        }
        showError(msg);
    }

    // ── Publish/draft toggle (edit mode only) ──────────────────────────
    function handlePublishToggle() {
        if (!projectId) return;
        var current = statusEl ? statusEl.value : initialStatus;
        var next = current === 'active' ? 'draft' : 'active';

        clearError();
        setAllBusy(true);
        if (publishBtn) publishBtn.textContent = next === 'active' ? 'Publishing…' : 'Saving…';

        postJson(
            '/api/backstage/projects?op=status&id=' + encodeURIComponent(projectId),
            { status: next }
        ).then(function (res) {
            if (!res.ok) { handleSaveError(res); return; }
            // Reflect new status in the select + initial baseline so a later
            // Save click doesn't re-fire the status endpoint.
            if (statusEl) statusEl.value = next;
            initialStatus = next;
            setAllBusy(false);
            restoreLabels();
            // Update the Publish button label to match the new state.
            if (publishBtn) publishBtn.textContent = next === 'active' ? 'Set to Draft' : 'Publish';
            labels.publish = publishBtn ? publishBtn.textContent.trim() : labels.publish;
        }).catch(function (e) {
            setAllBusy(false);
            restoreLabels();
            showError('Status change failed: ' + (e && e.message || 'unknown error'));
        });
    }

    // ── Archive (edit mode only) ──────────────────────────────────────
    function handleArchive() {
        if (!projectId) return;
        if (!confirm('Archive this project? It will be hidden from public listings. You can restore it by changing the status back.')) {
            return;
        }

        clearError();
        setAllBusy(true);
        if (archiveBtn) archiveBtn.textContent = 'Archiving…';

        postJson(
            '/api/backstage/projects?op=status&id=' + encodeURIComponent(projectId),
            { status: 'archived' }
        ).then(function (res) {
            if (!res.ok) { handleSaveError(res); return; }
            window.location.href = '/backstage/projects?tab=projects';
        }).catch(function (e) {
            setAllBusy(false);
            restoreLabels();
            showError('Archive failed: ' + (e && e.message || 'unknown error'));
        });
    }

    // ── Thin fetch wrapper that always returns { ok, status, data } ────
    function postJson(url, body) {
        return fetch(url, {
            method:  'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(body || {})
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                return { ok: r.ok, status: r.status, data: data };
            });
        });
    }
})();

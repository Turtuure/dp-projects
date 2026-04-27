/**
 * Projects admin: tabs, tables, and create/edit modal.
 *
 * Exposes: window.ProjectModal.open(mode, project?)
 *   mode    : 'create' | 'edit'
 *   project : existing project object (required for edit mode)
 *
 * Uses location.reload() after any mutation as an MVP simplification
 * (matching events admin behaviour).
 */
(function (global) {
    'use strict';

    var _backdrop = null;
    var _modal    = null;
    var _mode     = 'create';
    var _project  = null;

    var data = global.DAEMS_PROJECTS_TAB || { proposals: { items: [] }, projects: { items: [] }, comments: { items: [] }, activeTab: 'proposals' };
    var projects = (data.projects && Array.isArray(data.projects.items)) ? data.projects.items.slice() : [];
    var comments = (data.comments && Array.isArray(data.comments.items)) ? data.comments.items.slice() : [];

    var categoryLabels = {
        community:  'Community',
        technology: 'Technology',
        events:     'Events',
        research:   'Research'
    };

    function escHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    // =========================================================================
    // Tab switching
    // =========================================================================
    function initTabs() {
        var buttons = document.querySelectorAll('.proj-tab[data-tab]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-tab');
                if (!tab) return;
                activateTab(tab);
                // Update URL without reloading
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    url.searchParams.delete('highlight');
                    window.history.replaceState({}, '', url.toString());
                } catch (e) { /* ignore */ }
            });
        });
    }

    function activateTab(tab) {
        document.querySelectorAll('.proj-tab').forEach(function (btn) {
            var isActive = btn.getAttribute('data-tab') === tab;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        document.querySelectorAll('.proj-tab-content').forEach(function (sec) {
            sec.classList.toggle('is-active', sec.getAttribute('data-tab-content') === tab);
        });
    }

    // =========================================================================
    // Proposals tab — approve/reject inline
    // =========================================================================
    function initProposals() {
        document.querySelectorAll('[data-proposal-approve]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id   = btn.getAttribute('data-proposal-approve');
                var note = noteFor(id);
                askConfirm({
                    title: 'Approve proposal',
                    message: 'Approve this proposal and create the project as a draft?',
                    confirmLabel: 'Approve'
                }).then(function (ok) {
                    if (!ok) return;
                    btn.disabled = true;
                    fetch('/api/backstage/proposals?op=approve&id=' + encodeURIComponent(id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ note: note })
                    }).then(function (r) {
                        if (r.ok) { location.reload(); }
                        else { btn.disabled = false; alert('Failed to approve proposal.'); }
                    }).catch(function () { btn.disabled = false; alert('Network error.'); });
                });
            });
        });
        document.querySelectorAll('[data-proposal-reject]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id   = btn.getAttribute('data-proposal-reject');
                var note = noteFor(id);
                askConfirm({
                    title: 'Reject proposal',
                    message: 'Reject this proposal?',
                    confirmLabel: 'Reject',
                    danger: true
                }).then(function (ok) {
                    if (!ok) return;
                    btn.disabled = true;
                    fetch('/api/backstage/proposals?op=reject&id=' + encodeURIComponent(id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ note: note })
                    }).then(function (r) {
                        if (r.ok || r.status === 204) { location.reload(); }
                        else { btn.disabled = false; alert('Failed to reject proposal.'); }
                    }).catch(function () { btn.disabled = false; alert('Network error.'); });
                });
            });
        });
    }

    function noteFor(proposalId) {
        var el = document.querySelector('[data-proposal-note="' + cssEscape(proposalId) + '"]');
        return el ? el.value.trim() : '';
    }

    function cssEscape(s) {
        if (global.CSS && typeof global.CSS.escape === 'function') return global.CSS.escape(s);
        return String(s).replace(/"/g, '\\"');
    }

    // =========================================================================
    // askConfirm — promise-based confirm dialog reusing .evt-modal styling
    // =========================================================================
    function askConfirm(opts) {
        opts = opts || {};
        var title   = opts.title   || 'Confirm';
        var message = opts.message || 'Are you sure?';
        var okLabel = opts.confirmLabel || 'Confirm';
        var danger  = !!opts.danger;

        return new Promise(function (resolve) {
            var backdrop = document.createElement('div');
            backdrop.className = 'evt-modal-backdrop';
            backdrop.setAttribute('role', 'dialog');
            backdrop.setAttribute('aria-modal', 'true');
            backdrop.innerHTML =
                '<div class="evt-modal" style="max-width:480px;">' +
                    '<div class="evt-modal__header">' +
                        '<h2 class="evt-modal__title">' + escHtml(title) + '</h2>' +
                        '<button type="button" class="evt-modal__close" data-confirm-cancel aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<div class="evt-modal__body"><p style="margin:0;white-space:pre-line;">' + escHtml(message) + '</p></div>' +
                    '<div class="evt-modal__footer">' +
                        '<button type="button" class="btn btn--ghost" data-confirm-cancel>Cancel</button>' +
                        '<button type="button" class="btn ' + (danger ? 'btn--danger' : 'btn--primary') + '" data-confirm-ok>' + escHtml(okLabel) + '</button>' +
                    '</div>' +
                '</div>';
            document.body.appendChild(backdrop);

            function finish(result) {
                document.removeEventListener('keydown', onKey);
                if (backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
                resolve(result);
            }
            function onKey(e) {
                if (e.key === 'Escape') finish(false);
                else if (e.key === 'Enter') finish(true);
            }
            backdrop.querySelectorAll('[data-confirm-cancel]').forEach(function (b) {
                b.addEventListener('click', function () { finish(false); });
            });
            backdrop.querySelector('[data-confirm-ok]').addEventListener('click', function () { finish(true); });
            backdrop.addEventListener('click', function (e) { if (e.target === backdrop) finish(false); });
            document.addEventListener('keydown', onKey);

            var okBtn = backdrop.querySelector('[data-confirm-ok]');
            if (okBtn) okBtn.focus();
        });
    }

    // =========================================================================
    // Projects tab — table render + filter + actions
    // =========================================================================
    var filterStatus   = '';
    var filterCategory = '';
    var filterSearch   = '';

    function statusPill(status) {
        return '<span class="proj-pill proj-pill--' + escHtml(status) + '">' + escHtml(status) + '</span>';
    }

    function filteredProjects() {
        return projects.filter(function (p) {
            if (filterStatus && p.status !== filterStatus) return false;
            if (filterCategory && p.category !== filterCategory) return false;
            if (filterSearch) {
                var q = filterSearch.toLowerCase();
                if ((p.title || '').toLowerCase().indexOf(q) === -1) return false;
            }
            return true;
        });
    }

    function projCoverageBadge(id, coverage) {
        coverage = coverage || {};
        var locales = ['fi_FI', 'en_GB', 'sw_TZ'];
        var dots = locales.map(function (loc) {
            var c = coverage[loc] || { filled: 0, total: 3 };
            var cls;
            if (c.total > 0 && c.filled >= c.total) cls = 'complete';
            else if (c.filled === 0)                cls = 'empty';
            else                                    cls = 'partial';
            return '<span class="coverage-dot ' + cls + '" data-loc="' + loc +
                '" title="' + loc + ' ' + c.filled + '/' + c.total + '"></span>';
        }).join('');
        return '<span class="coverage-badge" data-entity-id="' + escHtml(id) +
               '" title="fi_FI / en_GB / sw_TZ translations">' + dots + '</span>';
    }

    function renderProjectsTable() {
        var tbody   = document.getElementById('proj-tbody');
        var countEl = document.getElementById('proj-count');
        if (!tbody) return;
        var rows = filteredProjects();
        if (countEl) countEl.textContent = 'Total: ' + rows.length;
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="proj-empty">No projects found.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (p) {
            var id        = p.id || '';
            var status    = p.status || 'draft';
            var featured  = !!p.featured;
            var starClass = 'featured-star' + (featured ? '' : ' featured-star--off');
            var starTitle = featured ? 'Unfeature' : 'Feature';
            var catLabel  = categoryLabels[p.category] || (p.category || '');
            return '<tr data-row-id="' + escHtml(id) + '">' +
                '<td><strong>' + escHtml(p.title || '') + '</strong></td>' +
                '<td>' + escHtml(catLabel) + '</td>' +
                '<td>' +
                    '<select class="proj-status-select" data-status-id="' + escHtml(id) + '" data-current="' + escHtml(status) + '">' +
                        ['draft', 'active', 'archived'].map(function (s) {
                            return '<option value="' + s + '"' + (s === status ? ' selected' : '') + '>' + s + '</option>';
                        }).join('') +
                    '</select>' +
                '</td>' +
                '<td class="proj-featured-cell">' +
                    '<button type="button" class="' + starClass + '" data-featured-id="' + escHtml(id) + '" data-featured="' + (featured ? '1' : '0') + '" title="' + starTitle + '">' +
                        (featured ? '★' : '☆') +
                    '</button>' +
                '</td>' +
                '<td class="proj-coverage-cell">' + projCoverageBadge(id, p.coverage) + '</td>' +
                '<td>' + (p.participants_count || 0) + '</td>' +
                '<td>' + (p.comments_count || 0) + '</td>' +
                '<td class="proj-actions">' +
                    '<button type="button" class="proj-action" data-action="edit" data-id="' + escHtml(id) + '" title="Edit">' +
                        '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.42l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.82z"/></svg>' +
                    '</button>' +
                '</td>' +
            '</tr>';
        }).join('');

        // Edit button
        tbody.querySelectorAll('[data-action="edit"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                var p  = projects.find(function (x) { return x.id === id; });
                if (p) ProjectModal.open('edit', p);
            });
        });

        // Status change
        tbody.querySelectorAll('.proj-status-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                var id   = sel.getAttribute('data-status-id');
                var prev = sel.getAttribute('data-current');
                var next = sel.value;
                if (next === prev) return;
                askConfirm({
                    title: 'Change project status',
                    message: 'Change status from "' + prev + '" to "' + next + '"?',
                    confirmLabel: 'Change',
                    danger: next === 'archived'
                }).then(function (ok) {
                    if (!ok) { sel.value = prev; return; }
                    fetch('/api/backstage/projects?op=status&id=' + encodeURIComponent(id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: next })
                    }).then(function (r) {
                        if (r.ok) { location.reload(); }
                        else { sel.value = prev; alert('Failed to change status.'); }
                    }).catch(function () { sel.value = prev; alert('Network error.'); });
                });
            });
        });

        // Featured toggle
        tbody.querySelectorAll('[data-featured-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id   = btn.getAttribute('data-featured-id');
                var cur  = btn.getAttribute('data-featured') === '1';
                var next = !cur;
                fetch('/api/backstage/projects?op=featured&id=' + encodeURIComponent(id), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ featured: next })
                }).then(function (r) {
                    if (r.ok) { location.reload(); }
                    else { alert('Failed to toggle featured.'); }
                }).catch(function () { alert('Network error.'); });
            });
        });
    }

    function initProjectFilters() {
        var s = document.getElementById('proj-filter-status');
        if (s) s.addEventListener('change', function () { filterStatus = s.value; renderProjectsTable(); });
        var c = document.getElementById('proj-filter-category');
        if (c) c.addEventListener('change', function () { filterCategory = c.value; renderProjectsTable(); });
        var q = document.getElementById('proj-filter-search');
        if (q) q.addEventListener('input', function () { filterSearch = q.value; renderProjectsTable(); });
    }

    // =========================================================================
    // Comments tab
    // =========================================================================
    var commentProjectFilter = '';

    function filteredComments() {
        if (!commentProjectFilter) return comments;
        return comments.filter(function (c) { return c.project_id === commentProjectFilter; });
    }

    function renderCommentsTable() {
        var tbody   = document.getElementById('proj-comments-tbody');
        var countEl = document.getElementById('proj-comment-count');
        if (!tbody) return;
        var rows = filteredComments();
        if (countEl) countEl.textContent = 'Total: ' + rows.length;
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="proj-empty">No comments found.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (c) {
            var cid = c.comment_id || '';
            var pid = c.project_id || '';
            return '<tr>' +
                '<td>' + escHtml((c.created_at || '').substring(0, 16).replace('T', ' ')) + '</td>' +
                '<td>' + escHtml(c.project_title || '') + '</td>' +
                '<td>' + escHtml(c.author_name || '') + '</td>' +
                '<td class="proj-comment-content">' + escHtml(c.content || '') + '</td>' +
                '<td class="proj-actions">' +
                    '<input type="text" class="proj-reason" data-reason="' + escHtml(cid) + '" placeholder="Reason (optional)" />' +
                    '<button type="button" class="btn btn--ghost btn--sm proj-action--danger" data-delete-comment="' + escHtml(cid) + '" data-project-id="' + escHtml(pid) + '">Delete</button>' +
                '</td>' +
            '</tr>';
        }).join('');

        tbody.querySelectorAll('[data-delete-comment]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cid    = btn.getAttribute('data-delete-comment');
                var pid    = btn.getAttribute('data-project-id');
                var resEl  = tbody.querySelector('[data-reason="' + cssEscape(cid) + '"]');
                var reason = resEl ? resEl.value.trim() : '';
                askConfirm({
                    title: 'Delete comment',
                    message: 'Delete this comment? An audit entry will be recorded.',
                    confirmLabel: 'Delete',
                    danger: true
                }).then(function (ok) {
                    if (!ok) return;
                    btn.disabled = true;
                    fetch('/api/backstage/projects?op=delete_comment&id=' + encodeURIComponent(pid) + '&comment_id=' + encodeURIComponent(cid), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ reason: reason })
                    }).then(function (r) {
                        if (r.ok || r.status === 204) { location.reload(); }
                        else { btn.disabled = false; alert('Failed to delete comment.'); }
                    }).catch(function () { btn.disabled = false; alert('Network error.'); });
                });
            });
        });
    }

    function initCommentsFilter() {
        var sel = document.getElementById('proj-comment-filter-project');
        if (!sel) return;
        // Populate with distinct project titles
        var seen = {};
        var opts = [];
        comments.forEach(function (c) {
            var id = c.project_id;
            if (!id || seen[id]) return;
            seen[id] = true;
            opts.push({ id: id, title: c.project_title || '(untitled)' });
        });
        opts.sort(function (a, b) { return a.title.localeCompare(b.title); });
        opts.forEach(function (o) {
            var opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.title;
            sel.appendChild(opt);
        });
        sel.addEventListener('change', function () {
            commentProjectFilter = sel.value;
            renderCommentsTable();
        });
    }

    // =========================================================================
    // Create/Edit modal
    // =========================================================================
    function buildModal() {
        if (_backdrop) return;

        _backdrop = document.createElement('div');
        _backdrop.className = 'evt-modal-backdrop';
        _backdrop.hidden = true;
        _backdrop.setAttribute('aria-hidden', 'true');

        _modal = document.createElement('div');
        _modal.className = 'evt-modal evt-modal--wide';
        _modal.setAttribute('role', 'dialog');
        _modal.setAttribute('aria-modal', 'true');
        _modal.setAttribute('aria-labelledby', 'proj-modal-title');

        _modal.innerHTML =
            '<div class="evt-modal__header">' +
                '<h2 class="evt-modal__title" id="proj-modal-title">New Project</h2>' +
                '<button type="button" class="evt-modal__close" id="proj-modal-close" aria-label="Close">&times;</button>' +
            '</div>' +
            '<div class="evt-modal__body">' +
                '<form class="evt-form" id="proj-form" novalidate>' +

                    // Locale-cards at the top (title/summary/description per locale).
                    '<div class="evt-form-row evt-form-row--full">' +
                        '<div class="evt-form-field">' +
                            '<div class="locale-cards-container"' +
                                 ' data-kind="project" data-entity-id="">' +
                                '<div class="locale-cards-grid" role="tablist" aria-label="Locale translations"></div>' +
                                '<div class="locale-cards-editor">' +
                                    '<div class="locale-cards-fields"></div>' +
                                    '<div class="locale-cards-actions">' +
                                        '<button type="button" class="btn btn--primary locale-cards-save">Save</button>' +
                                        '<span class="locale-cards-status" aria-live="polite"></span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    // Non-translated chrome: category + icon.
                    '<div class="evt-form-row">' +
                        '<div class="evt-form-field">' +
                            '<label class="evt-form-label evt-form-label--required" for="proj-f-category">Category</label>' +
                            '<select id="proj-f-category" class="evt-form-select" required>' +
                                '<option value="">— select —</option>' +
                                '<option value="community">Community</option>' +
                                '<option value="technology">Technology</option>' +
                                '<option value="events">Events</option>' +
                                '<option value="research">Research</option>' +
                            '</select>' +
                            '<span class="evt-error-msg" id="proj-err-category"></span>' +
                        '</div>' +
                        '<div class="evt-form-field">' +
                            '<label class="evt-form-label" for="proj-f-icon">Icon (Bootstrap Icons class)</label>' +
                            '<input type="text" id="proj-f-icon" class="evt-form-input" placeholder="bi-lightbulb">' +
                        '</div>' +
                    '</div>' +

                '</form>' +
            '</div>' +
            '<div class="evt-modal__footer">' +
                '<span class="evt-error-msg" id="proj-save-error"></span>' +
                '<button type="button" class="btn btn--ghost" id="proj-modal-cancel">Cancel</button>' +
                '<button type="button" class="btn btn--primary" id="proj-modal-save">Save</button>' +
            '</div>';

        _backdrop.appendChild(_modal);
        document.body.appendChild(_backdrop);

        document.getElementById('proj-modal-close').addEventListener('click', close);
        document.getElementById('proj-modal-cancel').addEventListener('click', close);
        _backdrop.addEventListener('click', function (e) { if (e.target === _backdrop) close(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && _backdrop && !_backdrop.hidden) close();
        });
        document.getElementById('proj-modal-save').addEventListener('click', handleSave);
    }

    function close() {
        if (_backdrop) {
            _backdrop.hidden = true;
            _backdrop.setAttribute('aria-hidden', 'true');
        }
        setSaveError('');
    }

    function setSaveError(msg) {
        var el = document.getElementById('proj-save-error');
        if (el) el.textContent = msg || '';
    }

    function setSaving(saving) {
        var btn = document.getElementById('proj-modal-save');
        if (btn) { btn.disabled = saving; btn.textContent = saving ? 'Saving…' : 'Save'; }
    }

    function readLocaleDraft() {
        if (!_modal) return {};
        var container = _modal.querySelector('.locale-cards-container');
        if (!container) return {};
        var draft = {};
        container.querySelectorAll('.locale-cards-fields input, .locale-cards-fields textarea').forEach(function (i) {
            draft[i.name] = i.value;
        });
        return draft;
    }

    function validate() {
        var errors = {};
        var cat = val('proj-f-category');
        if (!cat) errors.category = 'Please select a category.';

        if (_mode === 'create') {
            // Title/summary/description validated from the active locale card
            // on create (defaults to fi_FI) — the server needs them to create
            // the project row and its source-locale translation.
            var draft   = readLocaleDraft();
            var title   = (draft.title       || '').trim();
            var summary = (draft.summary     || '').trim();
            var desc    = (draft.description || '').trim();
            if (title.length < 3 || title.length > 200) {
                errors.title = 'Title must be 3–200 characters (enter it under the Suomi card).';
            }
            if (summary.length < 10) {
                errors.summary = 'Summary must be at least 10 characters (enter it under the Suomi card).';
            }
            if (desc.length < 20) {
                errors.description = 'Description must be at least 20 characters (enter it under the Suomi card).';
            }
        }
        return errors;
    }

    function showFieldErrors(errors) {
        _modal.querySelectorAll('.evt-error-msg').forEach(function (el) {
            if (el.id && el.id.indexOf('proj-err-') === 0) el.textContent = '';
        });
        _modal.querySelectorAll('.is-error').forEach(function (el) { el.classList.remove('is-error'); });

        // Category is the only non-translated input with an in-line error slot.
        var fieldMap = {
            category: 'proj-f-category'
        };
        Object.keys(errors).forEach(function (f) {
            var inputId = fieldMap[f];
            if (inputId) {
                var input = document.getElementById(inputId);
                if (input) input.classList.add('is-error');
            }
            var errEl = document.getElementById('proj-err-' + f);
            if (errEl) errEl.textContent = errors[f];
        });

        // Translated-field errors surface on the locale-cards status bar.
        if (errors.title || errors.summary || errors.description) {
            var container = _modal.querySelector('.locale-cards-container');
            var status = container ? container.querySelector('.locale-cards-status') : null;
            if (status) {
                status.textContent = errors.title || errors.summary || errors.description;
                status.classList.remove('is-success');
                status.classList.add('is-error');
            }
        }
    }

    function populateForm(p) {
        document.getElementById('proj-f-category').value = p.category || '';
        document.getElementById('proj-f-icon').value     = p.icon || '';
        mountProjectLocaleCards(p);
    }

    function mountProjectLocaleCards(p) {
        var container = _modal.querySelector('.locale-cards-container');
        if (!container || !global.LocaleCards) return;
        var translations = (p && p.translations) ? p.translations : {};
        // Legacy fallback: fold top-level title/summary/description into fi_FI
        // when the backend hasn't yet started returning a `translations` map.
        if (!translations.fi_FI && (p.title || p.summary || p.description)) {
            translations = Object.assign({}, translations, {
                fi_FI: {
                    title:       p.title       || '',
                    summary:     p.summary     || '',
                    description: p.description || ''
                }
            });
        }
        var coverage = (p && p.coverage) ? p.coverage : {};
        global.LocaleCards.mount(container, {
            kind:         'project',
            entityId:     p && p.id ? p.id : '',
            translations: translations,
            coverage:     coverage
        });
    }

    function initEmptyProjectCards() {
        var container = _modal.querySelector('.locale-cards-container');
        if (!container || !global.LocaleCards) return;
        global.LocaleCards.mount(container, {
            kind:         'project',
            entityId:     '',
            translations: {},
            coverage: {
                fi_FI: { filled: 0, total: 3 },
                en_GB: { filled: 0, total: 3 },
                sw_TZ: { filled: 0, total: 3 }
            }
        });
    }

    function fetchAdminProject(id) {
        return fetch('/api/v1/backstage/projects/' + encodeURIComponent(id), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json().catch(function () { return null; });
        }).then(function (data) {
            if (!data) return null;
            if (data.id && (data.translations || data.coverage)) return data;
            if (data.data && data.data.id) return data.data;
            return null;
        }).catch(function () { return null; });
    }

    function resetForm() {
        var f = document.getElementById('proj-form');
        if (f) f.reset();
    }

    function handleSave() {
        var errs = validate();
        if (Object.keys(errs).length > 0) { showFieldErrors(errs); return; }
        showFieldErrors({});
        setSaving(true);
        setSaveError('');

        var body = {
            category: val('proj-f-category'),
            icon:     val('proj-f-icon').trim() || null
        };

        if (_mode === 'create') {
            // Create flow seeds fi_FI source-locale translation alongside the
            // project row so the backend can build both in one request.
            var draft = readLocaleDraft();
            body.title         = (draft.title       || '').trim();
            body.summary       = (draft.summary     || '').trim();
            body.description   = (draft.description || '').trim();
            body.source_locale = 'fi_FI';
        }
        // In edit mode, title/summary/description are saved per-locale by
        // the locale-cards component. The update endpoint now only updates
        // category/icon chrome.

        var url;
        if (_mode === 'edit' && _project) {
            url = '/api/backstage/projects?op=update&id=' + encodeURIComponent(_project.id);
        } else {
            url = '/api/backstage/projects?op=create';
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().then(function (data) {
                return { ok: r.ok, status: r.status, data: data };
            }).catch(function () {
                return { ok: r.ok, status: r.status, data: {} };
            });
        }).then(function (res) {
            if (!res.ok) {
                setSaving(false);
                var msg = (res.data && (res.data.error || res.data.message)) || 'Save failed.';
                if (res.data && res.data.errors) {
                    var fieldErrs = {};
                    Object.keys(res.data.errors).forEach(function (k) {
                        fieldErrs[k] = (res.data.errors[k] || []).join(' ');
                    });
                    showFieldErrors(fieldErrs);
                }
                setSaveError(msg);
                return;
            }
            close();
            location.reload();
        }).catch(function () {
            setSaving(false);
            setSaveError('Network error.');
        });
    }

    var ProjectModal = {
        open: function (mode, project) {
            buildModal();
            _mode    = mode || 'create';
            _project = project || null;

            var titleEl = document.getElementById('proj-modal-title');
            if (titleEl) titleEl.textContent = _mode === 'edit' ? 'Edit Project' : 'New Project';

            resetForm();
            setSaveError('');
            showFieldErrors({});

            var saveBtn = document.getElementById('proj-modal-save');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }

            if (_mode === 'edit' && _project) {
                populateForm(_project);
                if (_project.id) {
                    fetchAdminProject(_project.id).then(function (full) {
                        if (full) {
                            _project = full;
                            mountProjectLocaleCards(full);
                        }
                    });
                }
            } else {
                initEmptyProjectCards();
            }

            _backdrop.hidden = false;
            _backdrop.setAttribute('aria-hidden', 'false');

            var first = _modal.querySelector('input, select, textarea');
            if (first) setTimeout(function () { first.focus(); }, 50);
        }
    };

    global.ProjectModal = ProjectModal;

    // =========================================================================
    // Initial wire-up
    // =========================================================================
    function boot() {
        initTabs();
        initProposals();
        initProjectFilters();
        renderProjectsTable();
        initCommentsFilter();
        renderCommentsTable();

        var btnNew = document.getElementById('btn-new-project');
        if (btnNew) {
            btnNew.addEventListener('click', function () { ProjectModal.open('create'); });
        }

        // Highlight a proposal row when ?highlight=<id> is present
        try {
            var params = new URLSearchParams(window.location.search);
            var hid = params.get('highlight');
            if (hid) {
                var el = document.getElementById('app-' + hid);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.style.outline = '3px solid var(--brand-primary)';
                    el.style.outlineOffset = '3px';
                    setTimeout(function () {
                        el.style.outline = '';
                        el.style.outlineOffset = '';
                    }, 2000);
                }
            }
        } catch (e) { /* ignore */ }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    // Coverage update — update cached project entry + repaint that row's
    // translation badge without a full page reload.
    global.DAEMS_PROJECTS_COVERAGE_UPDATE = function (entityId, coverage) {
        if (!entityId || !coverage) return;
        var p = projects.find(function (x) { return x.id === entityId; });
        if (p) p.coverage = coverage;
        var tbody = document.getElementById('proj-tbody');
        var cell = tbody && tbody.querySelector('tr[data-row-id="' + entityId + '"] .proj-coverage-cell');
        if (cell) cell.innerHTML = projCoverageBadge(entityId, coverage);
    };

    // Forward locale-cards saves to the list view so coverage badges repaint
    // without a full page reload (Task B4).
    document.addEventListener('locale-cards:saved', function (e) {
        if (!e || !e.detail || e.detail.kind !== 'project') return;
        if (typeof global.DAEMS_PROJECTS_COVERAGE_UPDATE === 'function') {
            global.DAEMS_PROJECTS_COVERAGE_UPDATE(e.detail.entityId, e.detail.coverage);
        }
    });
}(window));

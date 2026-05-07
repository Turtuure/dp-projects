/**
 * Projects admin list page — tabs, tables, inline confirmations.
 *
 * Create/edit have moved to dedicated sub-pages (see /backstage/projects/new
 * and /backstage/projects/edit) — this script no longer opens a modal. The
 * "+ New project" button is a plain link, the project name in each row is a
 * link, and the row-level Edit button navigates to the edit sub-page.
 *
 * What this file still owns:
 *   - Tab switching (proposals / projects / comments)
 *   - Inline approve/reject for project proposals
 *   - Status select + featured toggle on each project row (POST -> reload)
 *   - Recent-comments delete with reason input
 *   - askConfirm() promise-based modal for the confirmations above
 *
 * Uses location.reload() after any mutation as an MVP simplification
 * (matching events admin behaviour).
 */
(function (global) {
    'use strict';

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

    // =========================================================================
    // Tab switching — clickable KPI cards (.kpi-card--tab[data-tab]) drive
    // the .proj-tab-content panels below.
    // =========================================================================
    function initTabs() {
        var buttons = document.querySelectorAll('.kpi-card--tab[data-tab]');
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
        document.querySelectorAll('.kpi-card--tab').forEach(function (btn) {
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
            var editHref  = '/backstage/projects/edit?id=' + encodeURIComponent(id);
            return '<tr data-row-id="' + escHtml(id) + '">' +
                '<td><a class="proj-row-link" href="' + editHref + '"><strong>' + escHtml(p.title || '') + '</strong></a></td>' +
                '<td>' + escHtml(catLabel) + '</td>' +
                '<td>' +
                    '<select class="proj-status-select" name="status" aria-label="Status of ' + escHtml(p.title || 'project') + '" data-status-id="' + escHtml(id) + '" data-current="' + escHtml(status) + '">' +
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
                    '<a class="btn btn--icon" href="' + editHref + '" title="Edit" aria-label="Edit">' +
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.42l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.82z" fill="currentColor"/></svg>' +
                    '</a>' +
                '</td>' +
            '</tr>';
        }).join('');

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
    // Initial wire-up
    // =========================================================================
    function boot() {
        initTabs();
        initProposals();
        initProjectFilters();
        renderProjectsTable();
        initCommentsFilter();
        renderCommentsTable();

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
}(window));

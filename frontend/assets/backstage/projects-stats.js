/**
 * Projects KPI strip — fetches /api/backstage/projects.php?op=stats and refines
 * the three card-tabs (Projects / Proposals / Comments).
 *
 * The Projects card displays the total project count in the value slot, and a
 * "X drafts • Y featured" subtitle once stats arrive. The Proposals value is
 * also refreshed against the canonical pending count from the stats endpoint
 * (server-rendered initial value comes from the proposals list).
 *
 * Comments has no stats endpoint contribution today — the server-rendered
 * value (count of recent comments) is left as-is.
 */
(function () {
  'use strict';

  if (!document.querySelector('.kpi-card--tab[data-tab="projects"]')) return;

  fetch('/api/backstage/projects.php?op=stats')
    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (j) { render(j && j.data ? j.data : null); })
    .catch(function (e) { console.error('projects stats failed', e); });

  function render(data) {
    if (!data) return;
    document.querySelectorAll('.kpi-card--tab').forEach(function (c) { c.classList.remove('is-loading'); });

    var active   = (data.active            && typeof data.active.value            === 'number') ? data.active.value            : null;
    var drafts   = (data.drafts            && typeof data.drafts.value            === 'number') ? data.drafts.value            : null;
    var featured = (data.featured          && typeof data.featured.value          === 'number') ? data.featured.value          : null;
    var pending  = (data.pending_proposals && typeof data.pending_proposals.value === 'number') ? data.pending_proposals.value : null;

    // Projects card subtitle: "X drafts • Y featured"
    if (drafts !== null || featured !== null) {
      var parts = [];
      if (drafts   !== null) parts.push(drafts   + ' draft'    + (drafts   === 1 ? '' : 's'));
      if (featured !== null) parts.push(featured + ' featured');
      var sub = document.querySelector('[data-tab-subtitle="projects"]');
      if (sub && parts.length) sub.textContent = parts.join(' • ');
    }

    // Proposals value — refresh from canonical stats count.
    if (pending !== null) {
      setVal('proposals', pending);
    }

    // Active count is informational on the Projects card; we keep the total
    // project count in the value slot, so this is currently unused. If we
    // ever switch the Projects value to "active only", flip this.
    void active;
  }

  function setVal(tab, value) {
    var el = document.querySelector('.kpi-card--tab[data-tab="' + tab + '"] .kpi-card__value');
    if (el) el.textContent = String(value);
  }
})();

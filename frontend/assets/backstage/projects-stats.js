/**
 * Projects KPI strip — fetches /api/backstage/projects.php?op=stats and populates
 * KPI values + sparklines on the projects page.
 */
(function () {
  'use strict';

  if (!document.querySelector('.kpis-grid .kpi-card[data-kpi="active"]')) return;

  var KPI_COLORS = {
    active:            '#16a34a',
    drafts:            '#64748b',
    featured:          '#a855f7',
    pending_proposals: '#d97706',
  };

  fetch('/api/backstage/projects.php?op=stats')
    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function (j) { render(j && j.data ? j.data : null); })
    .catch(function (e) { console.error('projects stats failed', e); });

  function render(data) {
    if (!data) return;
    document.querySelectorAll('.kpi-card').forEach(function (c) { c.classList.remove('is-loading'); });

    Object.keys(KPI_COLORS).forEach(function (id) {
      setKpi(id, data[id]);
      initSpark(id, data[id] && data[id].sparkline);
    });
  }

  function setKpi(id, payload) {
    var el = document.querySelector('.kpi-card[data-kpi="' + id + '"] .kpi-card__value');
    if (el && payload) el.textContent = String(payload.value);
  }

  function initSpark(id, points) {
    var el = document.getElementById('spark-' + id);
    if (el && window.Sparkline) window.Sparkline.init(el, points || [], KPI_COLORS[id]);
  }
})();

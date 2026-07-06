(function () {
  'use strict';

  const palette = [
    '#2563eb', '#dc2626', '#059669', '#7c3aed', '#d97706', '#db2777',
    '#0891b2', '#65a30d', '#4f46e5', '#9333ea', '#ea580c', '#475569'
  ];
  const EXPORT_FORMATS = ['png', 'jpeg', 'jpg', 'tiff', 'pdf'];

  let currentMap = null;
  let pieChart = null;
  let barChart = null;
  let timelineChart = null;
  let currentPieTab = null;
  let currentBarTab = null;
  let currentMapVariable = null;
  let latestMapPoints = [];
  let expandedContentState = null;

  function rgba(hex, alpha) {
    const normalized = String(hex || '').replace('#', '');
    const safe = normalized.length === 3
      ? normalized.split('').map((char) => char + char).join('')
      : normalized.padEnd(6, '0').slice(0, 6);
    const num = Number.parseInt(safe, 16);
    const r = (num >> 16) & 255;
    const g = (num >> 8) & 255;
    const b = num & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  function tabMap(tabs) {
    return Object.fromEntries((tabs || []).map((tab) => [tab.key, tab]));
  }

  function colorMap(values) {
    const uniqueValues = [...new Set(values.filter(Boolean))];
    return Object.fromEntries(uniqueValues.map((value, index) => [value, palette[index % palette.length]]));
  }

  function buildDistribution(points, variable) {
    const counts = {};

    (points || []).forEach((point) => {
      const key = pointValue(point, variable);
      if (key === 'Unknown') {
        return;
      }

      counts[key] = (counts[key] || 0) + 1;
    });

    return counts;
  }

  function createPieChartSvg(distribution, totalCount, colors) {
    const size = 42;
    const radius = 17;
    const cx = 21;
    const cy = 21;

    const slices = Object.entries(distribution)
      .filter(([, count]) => count > 0)
      .map(([label, count]) => ({
        label,
        count,
        color: colors[label] || '#64748b',
      }));

    if (slices.length === 0) {
      return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
        <circle cx="${cx}" cy="${cy}" r="${radius}" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>
        <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
      </svg>`;
    }

    if (slices.length === 1) {
      return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
        <circle cx="${cx}" cy="${cy}" r="${radius}" fill="${slices[0].color}" stroke="white" stroke-width="2"/>
        <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
      </svg>`;
    }

    let currentAngle = -Math.PI / 2;
    const total = slices.reduce((sum, item) => sum + item.count, 0);
    let paths = '';

    slices.forEach((item) => {
      const angle = (item.count / total) * 2 * Math.PI;
      const endAngle = currentAngle + angle;
      const x1 = cx + radius * Math.cos(currentAngle);
      const y1 = cy + radius * Math.sin(currentAngle);
      const x2 = cx + radius * Math.cos(endAngle);
      const y2 = cy + radius * Math.sin(endAngle);
      const largeArc = angle > Math.PI ? 1 : 0;
      const d = `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`;
      paths += `<path d="${d}" fill="${item.color}" stroke="white" stroke-width="1"/>`;
      currentAngle = endAngle;
    });

    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
      <circle cx="${cx}" cy="${cy}" r="${radius + 1}" fill="white" stroke="#e2e8f0" stroke-width="1"/>
      ${paths}
      <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
    </svg>`;
  }

  function createSinglePointIcon(color) {
    return L.divIcon({
      html: `<span style="display:block;width:16px;height:16px;border-radius:9999px;background:${color};border:2px solid #ffffff;box-shadow:0 1px 3px rgba(0,0,0,0.4);"></span>`,
      className: 'pie-chart-cluster-icon',
      iconSize: [16, 16],
      iconAnchor: [8, 8],
    });
  }

  function pointValue(point, key) {
    const raw = point ? point[key] : null;
    return raw === null || raw === undefined || raw === '' ? 'Unknown' : String(raw);
  }

  function applyActiveButtonState(selector, activeKey) {
    document.querySelectorAll(selector).forEach((button) => {
      const key = button.dataset.pieTab || button.dataset.barTab || button.dataset.mapTab || '';
      button.classList.toggle('is-active', key === activeKey);
    });
  }

  function renderLegend(containerId, labels, colors) {
    const container = document.getElementById(containerId);
    if (!container) {
      return;
    }

    if (!labels.length) {
      container.innerHTML = '<div class="text-xs text-gray-500">No data available for the current filters.</div>';
      return;
    }

    const maxVisible = 8;
    const collapsed = labels.length > maxVisible;
    const visibleLabels = collapsed ? labels.slice(0, maxVisible) : labels;

    container.innerHTML = `
      <div class="space-y-2">
        <div class="flex flex-wrap gap-3">
          ${visibleLabels.map((label) => `
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2.5 py-1 text-xs text-gray-600">
              <span class="inline-block h-3 w-3 rounded-full" style="background:${colors[label]}"></span>
              <span>${label}</span>
            </div>
          `).join('')}
        </div>
        ${collapsed ? `
          <button type="button" data-legend-toggle="${containerId}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            Show ${labels.length - maxVisible} more
          </button>
          <div id="${containerId}Expanded" class="hidden flex-wrap gap-3">
            ${labels.slice(maxVisible).map((label) => `
              <div class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2.5 py-1 text-xs text-gray-600">
                <span class="inline-block h-3 w-3 rounded-full" style="background:${colors[label]}"></span>
                <span>${label}</span>
              </div>
            `).join('')}
          </div>
        ` : ''}
      </div>
    `;

    const toggle = container.querySelector(`[data-legend-toggle="${containerId}"]`);
    const expanded = document.getElementById(`${containerId}Expanded`);
    if (toggle && expanded) {
      toggle.addEventListener('click', () => {
        const isHidden = expanded.classList.contains('hidden');
        expanded.classList.toggle('hidden', !isHidden);
        expanded.classList.toggle('flex', isHidden);
        toggle.textContent = isHidden ? 'Show less' : `Show ${labels.length - maxVisible} more`;
      });
    }
  }

  function downloadDataUrl(dataUrl, fileName) {
    const link = document.createElement('a');
    link.href = dataUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  function dataUrlToPdf(dataUrl, fileName) {
    const jspdf = window.jspdf;
    if (!jspdf?.jsPDF) {
      downloadDataUrl(dataUrl, `${fileName}.png`);
      return;
    }
    const pdf = new jspdf.jsPDF('landscape', 'pt', 'a4');
    const img = new Image();
    img.onload = () => {
      const pageW = pdf.internal.pageSize.getWidth();
      const pageH = pdf.internal.pageSize.getHeight();
      const ratio = Math.min(pageW / img.width, pageH / img.height);
      const w = img.width * ratio;
      const h = img.height * ratio;
      pdf.addImage(dataUrl, 'PNG', (pageW - w) / 2, (pageH - h) / 2, w, h);
      pdf.save(`${fileName}.pdf`);
    };
    img.src = dataUrl;
  }

  function currentFormatFor(target) {
    const btn = document.querySelector(`[data-format-target="${target}"]`);
    const value = btn?.getAttribute('data-format') || 'png';
    return EXPORT_FORMATS.includes(value) ? value : 'png';
  }

  function cycleFormatFor(target) {
    const btn = document.querySelector(`[data-format-target="${target}"]`);
    if (!btn) return;
    const idx = EXPORT_FORMATS.indexOf(currentFormatFor(target));
    const next = EXPORT_FORMATS[(idx + 1) % EXPORT_FORMATS.length];
    btn.setAttribute('data-format', next);
    btn.setAttribute('title', `Format: ${next} (click to change)`);
    btn.setAttribute('aria-label', `Format ${next}`);
  }

  async function exportElement(element, format, fileName) {
    if (!element || typeof html2canvas === 'undefined') return;
    const canvas = await html2canvas(element, {
      useCORS: true,
      backgroundColor: '#ffffff',
      scale: 2,
      ignoreElements: (node) => !!node.closest?.('.dashboard-box-tools'),
    });
    const lower = String(format || 'png').toLowerCase();
    const mime = (lower === 'jpg' || lower === 'jpeg') ? 'image/jpeg' : 'image/png';
    const dataUrl = canvas.toDataURL(mime, 0.95);
    if (lower === 'pdf') {
      dataUrlToPdf(dataUrl, fileName);
      return;
    }
    const ext = lower === 'jpg' ? 'jpg' : lower === 'jpeg' ? 'jpeg' : lower === 'tiff' ? 'tiff' : 'png';
    downloadDataUrl(dataUrl, `${fileName}.${ext}`);
  }

  function resizeVisualsFor(targetId) {
    if ((targetId === 'pieBox' || targetId === 'pieContent') && pieChart) {
      setTimeout(() => { pieChart.resize(); pieChart.update('none'); }, 120);
    } else if ((targetId === 'barBox' || targetId === 'barContent') && barChart) {
      setTimeout(() => { barChart.resize(); barChart.update('none'); }, 120);
    } else if ((targetId === 'mapBox' || targetId === 'mapContent') && currentMap) {
      setTimeout(() => currentMap.invalidateSize(), 160);
    }
  }

  function closeExpandedContent() {
    if (!expandedContentState) {
      return;
    }

    const { content, placeholder, contentId } = expandedContentState;
    const modal = document.getElementById('dashboardExpandModal');
    const modalBody = document.getElementById('dashboardExpandBody');
    if (placeholder?.parentNode) {
      placeholder.parentNode.insertBefore(content, placeholder);
      placeholder.remove();
    }
    if (modalBody) {
      modalBody.innerHTML = '';
    }
    if (modal) {
      modal.classList.add('hidden');
    }
    document.body.classList.remove('dashboard-box-open');
    expandedContentState = null;
    resizeVisualsFor(contentId);
  }

  function openExpandedContent(contentId) {
    const content = document.getElementById(contentId);
    const modal = document.getElementById('dashboardExpandModal');
    const modalBody = document.getElementById('dashboardExpandBody');
    const modalTitle = document.getElementById('dashboardExpandTitle');
    if (!content || !modal || !modalBody || !modalTitle) {
      return;
    }

    closeExpandedContent();

    const placeholder = document.createComment(`expanded-content:${contentId}`);
    content.parentNode?.insertBefore(placeholder, content);
    modalBody.appendChild(content);
    modalTitle.textContent = content.getAttribute('data-expand-title') || 'Expanded chart';
    modal.classList.remove('hidden');
    document.body.classList.add('dashboard-box-open');
    expandedContentState = { content, placeholder, contentId };
    resizeVisualsFor(contentId);
  }

  function escapeCsvValue(value) {
    const normalized = String(value ?? '').replace(/\s+/g, ' ').trim();
    if (normalized.includes('"') || normalized.includes(',') || normalized.includes('\n')) {
      return `"${normalized.replace(/"/g, '""')}"`;
    }

    return normalized;
  }

  function enhanceAnimalDashboardModal(container) {
    if (typeof window.enhanceDashboardModalTable === 'function') {
      window.enhanceDashboardModalTable(container);
    }
  }

  function buildPieChart() {
    const tabs = tabMap(window.pieChartTabs || []);
    const keys = Object.keys(tabs);
    if (!keys.length) {
      return;
    }

    if (!currentPieTab || !tabs[currentPieTab]) {
      currentPieTab = keys[0];
    }

    applyActiveButtonState('[data-pie-tab]', currentPieTab);

    const current = tabs[currentPieTab];
    const chartData = current.data || {};
    const labels = Object.keys(chartData);
    const values = Object.values(chartData).map((value) => Number(value));
    const colors = colorMap(labels);
    const canvas = document.getElementById('pieTabbedChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    pieChart?.destroy();
    pieChart = new Chart(canvas, {
      type: 'pie',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: labels.map((label) => rgba(colors[label], 0.82)),
          borderColor: labels.map((label) => rgba(colors[label], 1)),
          borderWidth: 1,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: { display: true, text: current.label || 'Distribution' },
        },
      },
    });

    renderLegend('pieLegendScroller', labels, colors);
  }

  function buildBarChart() {
    const tabs = tabMap(window.barChartTabs || []);
    const keys = Object.keys(tabs);
    if (!keys.length) {
      return;
    }

    if (!currentBarTab || !tabs[currentBarTab]) {
      currentBarTab = keys[0];
    }

    applyActiveButtonState('[data-bar-tab]', currentBarTab);

    const current = tabs[currentBarTab];
    const chartData = current.data || {};
    const labels = Object.keys(chartData);
    const values = Object.values(chartData).map((value) => Number(value));
    const shortLabels = labels.map((label) => label.length > 18 ? `${label.slice(0, 15)}...` : label);
    const canvas = document.getElementById('barTabbedChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    barChart?.destroy();
    barChart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: shortLabels,
        datasets: [{
          label: current.label || 'Count',
          data: values,
          backgroundColor: rgba('#0891b2', 0.75),
          borderColor: rgba('#0f766e', 1),
          borderWidth: 1,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: { display: true, text: current.label || 'Counts' },
          tooltip: {
            callbacks: {
              title(context) {
                return labels[context[0].dataIndex] || '';
              },
            },
          },
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { ticks: { maxRotation: 50, minRotation: 0 } },
        },
      },
    });
  }

  function buildTimelineChart(data) {
    const canvas = document.getElementById('timelineChart');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }

    timelineChart?.destroy();
    timelineChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels: Object.keys(data || {}),
        datasets: [{
          label: 'Collected',
          data: Object.values(data || {}).map((value) => Number(value)),
          borderColor: rgba('#2563eb', 1),
          backgroundColor: rgba('#2563eb', 0.14),
          tension: 0.25,
          fill: true,
          pointRadius: 3,
          pointHoverRadius: 5,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });
  }

  function buildMapUrl() {
    const base = window.mapPointsUrl;
    if (!base) return null;

    const url = new URL(base, window.location.origin);
    const filters = window.activeFilters || {};
    if (filters.visualize_by) url.searchParams.set('visualize_by', filters.visualize_by);
    if (filters.sampleVisibility) url.searchParams.set('sampleVisibility', filters.sampleVisibility);
    if (filters.animal_species_filter) url.searchParams.set('animal_species_filter', filters.animal_species_filter);
    if (filters.sample_type_filter) url.searchParams.set('sample_type_filter', filters.sample_type_filter);
    if (filters.sampling_site_filter) url.searchParams.set('sampling_site_filter', filters.sampling_site_filter);
    if (filters.subProjectFilter) url.searchParams.set('subProjectFilter', filters.subProjectFilter);
    if (filters.startDate) url.searchParams.set('startDate', filters.startDate);
    if (filters.endDate) url.searchParams.set('endDate', filters.endDate);
    return url.toString();
  }

  async function loadAllMapPoints() {
    const baseUrl = buildMapUrl();
    if (!baseUrl) {
      return;
    }

    const all = [];
    let cursor = null;
    let safety = 0;
    while (safety < 100) {
      safety += 1;
      const url = new URL(baseUrl, window.location.origin);
      url.searchParams.set('limit', '1500');
      if (cursor) {
        url.searchParams.set('cursor', String(cursor));
      }

      const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
      if (!response.ok) {
        break;
      }

      const data = await response.json();
      const points = Array.isArray(data.points) ? data.points : [];
      all.push(...points);
      cursor = data.next_cursor;
      if (!cursor || points.length === 0) {
        break;
      }
    }

    latestMapPoints = all;
    buildMap(all);
  }

  function buildMap(points) {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || typeof L === 'undefined') {
      return;
    }

    const mapOptions = Array.isArray(window.mapColorVariableOptions) ? window.mapColorVariableOptions : [];
    if (!currentMapVariable || !mapOptions.some((option) => option.key === currentMapVariable)) {
      currentMapVariable = mapOptions[0]?.key || 'species';
    }

    applyActiveButtonState('[data-map-tab]', currentMapVariable);

    if (currentMap) {
      currentMap.remove();
      currentMap = null;
    }

    mapContainer.innerHTML = '';
    mapContainer._leaflet_id = null;

    const map = L.map('map').setView([-28.5595, 22.9375], 5);
    currentMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors',
    }).addTo(map);

    const validPoints = (points || []).filter((point) =>
      point.latitude && point.longitude && Number.isFinite(point.latitude) && Number.isFinite(point.longitude)
    );
    const labels = validPoints.map((point) => pointValue(point, currentMapVariable));
    const colors = colorMap(labels);

    const clusters = L.markerClusterGroup({
      maxClusterRadius: 50,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
      iconCreateFunction(cluster) {
        const clusterPoints = cluster.getAllChildMarkers()
          .map((marker) => marker.pointData)
          .filter(Boolean);
        const distribution = buildDistribution(clusterPoints, currentMapVariable);

        return L.divIcon({
          html: createPieChartSvg(distribution, cluster.getChildCount(), colors),
          className: 'pie-chart-cluster-icon',
          iconSize: [42, 42],
          iconAnchor: [21, 21],
        });
      },
    });

    validPoints.forEach((point) => {
      const value = pointValue(point, currentMapVariable);
      const color = colors[value] || '#64748b';
      const latLng = [point.latitude, point.longitude];
      const tooltip = `
        <strong>Sample code:</strong> ${point.code || 'Unknown'}<br>
        <strong>Animal code:</strong> ${point.animal_code || 'Unknown'}<br>
        <strong>Species:</strong> ${point.species || 'Unknown'}<br>
        <strong>Sample type:</strong> ${point.type || 'Unknown'}<br>
        <strong>Sex:</strong> ${point.sex || 'Unknown'}<br>
        <strong>Sampling site:</strong> ${point.sampling_site || 'Unknown'}<br>
      `;

      const marker = L.marker(latLng, {
        icon: createSinglePointIcon(color),
      });
      marker.pointData = point;
      marker.bindTooltip(tooltip, { sticky: true });
      clusters.addLayer(marker);
    });

    map.addLayer(clusters);
    if (validPoints.length) {
      map.fitBounds(L.latLngBounds(validPoints.map((point) => [point.latitude, point.longitude])), {
        padding: [30, 30],
        maxZoom: 8,
      });
    }

    renderLegend('mapLegend', [...new Set(labels)].sort((a, b) => a.localeCompare(b)), colors);
  }

  function initializeTabHandlers() {
    document.querySelectorAll('[data-pie-tab]').forEach((button) => {
      if (button.dataset.boundClick === '1') {
        return;
      }
      button.dataset.boundClick = '1';
      button.addEventListener('click', () => {
        currentPieTab = button.dataset.pieTab || null;
        buildPieChart();
      });
    });

    document.querySelectorAll('[data-bar-tab]').forEach((button) => {
      if (button.dataset.boundClick === '1') {
        return;
      }
      button.dataset.boundClick = '1';
      button.addEventListener('click', () => {
        currentBarTab = button.dataset.barTab || null;
        buildBarChart();
      });
    });

    document.querySelectorAll('[data-map-tab]').forEach((button) => {
      if (button.dataset.boundClick === '1') {
        return;
      }
      button.dataset.boundClick = '1';
      button.addEventListener('click', () => {
        currentMapVariable = button.dataset.mapTab || null;
        buildMap(latestMapPoints);
      });
    });
  }

  function updateStatistics(stats) {
    window.timelineData = (stats && stats.collection_timeline) || {};
    buildTimelineChart(window.timelineData);
  }

  function updateCharts(data) {
    window.pieChartTabs = data.pieChartTabs || [];
    window.barChartTabs = data.barChartTabs || [];
    window.mapColorVariableOptions = data.mapColorVariableOptions || [];
    buildPieChart();
    buildBarChart();
  }

  function appendFilters(url) {
    const u = new URL(url, window.location.origin);
    const filters = window.activeFilters || {};
    if (filters.visualize_by) u.searchParams.set('visualize_by', filters.visualize_by);
    if (filters.sampleVisibility) u.searchParams.set('sampleVisibility', filters.sampleVisibility);
    if (filters.animal_species_filter) u.searchParams.set('animal_species_filter', filters.animal_species_filter);
    if (filters.sample_type_filter) u.searchParams.set('sample_type_filter', filters.sample_type_filter);
    if (filters.sampling_site_filter) u.searchParams.set('sampling_site_filter', filters.sampling_site_filter);
    if (filters.subProjectFilter) u.searchParams.set('subProjectFilter', filters.subProjectFilter);
    if (filters.startDate) u.searchParams.set('startDate', filters.startDate);
    if (filters.endDate) u.searchParams.set('endDate', filters.endDate);
    return u;
  }

  window.loadAnimalDashboardModal = function (modalId) {
    const url = window.modalTableUrls ? window.modalTableUrls[modalId] : null;
    if (!url) return;

    const el = document.getElementById(`${modalId}Content`);
    if (!el) return;

    el.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(appendFilters(url).toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => response.text())
      .then((html) => {
        el.innerHTML = html;
        enhanceAnimalDashboardModal(el);
      })
      .catch(() => {
        el.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  };

  document.addEventListener('click', function (event) {
    const expandBtn = event.target.closest('[data-expand-target]');
    if (expandBtn) {
      event.preventDefault();
      const targetId = expandBtn.getAttribute('data-expand-target');
      if (!targetId) return;
      openExpandedContent(targetId);
      return;
    }

    const closeExpandBtn = event.target.closest('[data-expand-close]');
    if (closeExpandBtn) {
      event.preventDefault();
      closeExpandedContent();
      return;
    }

    const formatBtn = event.target.closest('[data-format-target]');
    if (formatBtn) {
      event.preventDefault();
      cycleFormatFor(formatBtn.getAttribute('data-format-target'));
      return;
    }

    const downloadBtn = event.target.closest('[data-download-target]');
    if (downloadBtn) {
      event.preventDefault();
      const target = downloadBtn.getAttribute('data-download-target');
      if (target === 'pie') exportElement(document.getElementById('pieContent'), currentFormatFor('pie'), 'animal-samples-pie-chart');
      if (target === 'bar') exportElement(document.getElementById('barContent'), currentFormatFor('bar'), 'animal-samples-bar-chart');
      if (target === 'map') exportElement(document.getElementById('mapContent'), currentFormatFor('map'), 'animal-samples-map');
      return;
    }

    const link = event.target && event.target.closest ? event.target.closest('[data-modal-content] nav a[href]') : null;
    if (!link) {
      return;
    }

    const container = link.closest('[data-modal-content]');
    if (!container) {
      return;
    }

    event.preventDefault();
    container.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(appendFilters(link.getAttribute('href')).toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => response.text())
      .then((html) => {
        container.innerHTML = html;
        enhanceAnimalDashboardModal(container);
      })
      .catch(() => {
        container.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeExpandedContent();
    }
  });

  function initializeCharts() {
    initializeTabHandlers();
    buildPieChart();
    buildBarChart();
    buildTimelineChart(window.timelineData || {});
    loadAllMapPoints();
  }

  document.addEventListener('DOMContentLoaded', initializeCharts);
  document.addEventListener('livewire:init', initializeCharts);

  Livewire.on('filtersUpdated', (payload) => {
    closeExpandedContent();
    const data = payload && payload.data ? payload.data : {};
    window.activeFilters = data.activeFilters || window.activeFilters || {};
    window.mapPointsUrl = data.mapPointsUrl || window.mapPointsUrl || null;
    window.modalTableUrls = data.modalTableUrls || window.modalTableUrls || {};
    updateStatistics(data.descriptive_stats || {});
    updateCharts(data);
    loadAllMapPoints();
  });
})();

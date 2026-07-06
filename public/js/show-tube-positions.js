(function () {
  'use strict';

  const palette = [
    '#2563eb',
    '#dc2626',
    '#059669',
    '#7c3aed',
    '#d97706',
    '#db2777',
    '#0891b2',
    '#65a30d',
    '#4f46e5',
    '#9333ea',
    '#ea580c',
    '#475569'
  ];

  let currentMap = null;
  let pieChart = null;
  let barChart = null;
  let timelineChart = null;
  let currentPieTab = null;
  let currentBarTab = null;
  let currentMapVariable = null;
  let latestMapPoints = [];

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

  function getPieTabs() {
    return Array.isArray(window.pieChartTabs) ? window.pieChartTabs : [];
  }

  function getBarTabs() {
    return Array.isArray(window.barChartTabs) ? window.barChartTabs : [];
  }

  function getMapVariableOptions() {
    return Array.isArray(window.mapColorVariableOptions) ? window.mapColorVariableOptions : [];
  }

  function pointValue(point, key) {
    if (!point) {
      return 'Unknown';
    }

    const raw = point[key];
    if (raw === null || raw === undefined || raw === '') {
      return 'Unknown';
    }

    return String(raw);
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
    const isCollapsed = labels.length > maxVisible;
    const visibleLabels = isCollapsed ? labels.slice(0, maxVisible) : labels;

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
        ${isCollapsed ? `
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

  function buildPieChart() {
    const tabs = tabMap(getPieTabs());
    const availableKeys = Object.keys(tabs);
    if (!availableKeys.length) {
      return;
    }

    if (!currentPieTab || !tabs[currentPieTab]) {
      currentPieTab = availableKeys[0];
    }

    applyActiveButtonState('[data-pie-tab]', currentPieTab);

    const current = tabs[currentPieTab];
    const chartData = current?.data || {};
    const labels = Object.keys(chartData);
    const values = Object.values(chartData).map((value) => Number(value));
    const colors = colorMap(labels);
    const canvas = document.getElementById('pieTabbedChart');

    if (!canvas) {
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
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: { display: true, text: current?.label || 'Distribution' }
        }
      }
    });

    renderLegend('pieLegendScroller', labels, colors);
  }

  function buildBarChart() {
    const tabs = tabMap(getBarTabs());
    const availableKeys = Object.keys(tabs);
    if (!availableKeys.length) {
      return;
    }

    if (!currentBarTab || !tabs[currentBarTab]) {
      currentBarTab = availableKeys[0];
    }

    applyActiveButtonState('[data-bar-tab]', currentBarTab);

    const current = tabs[currentBarTab];
    const chartData = current?.data || {};
    const labels = Object.keys(chartData);
    const values = Object.values(chartData).map((value) => Number(value));
    const shortLabels = labels.map((label) => label.length > 18 ? `${label.slice(0, 15)}...` : label);
    const canvas = document.getElementById('barTabbedChart');

    if (!canvas) {
      return;
    }

    barChart?.destroy();

    barChart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: shortLabels,
        datasets: [{
          label: current?.label || 'Count',
          data: values,
          backgroundColor: rgba('#0891b2', 0.75),
          borderColor: rgba('#0f766e', 1),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          title: { display: true, text: current?.label || 'Counts' },
          tooltip: {
            callbacks: {
              title(context) {
                return labels[context[0].dataIndex] || '';
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { ticks: { maxRotation: 50, minRotation: 0 } }
        }
      }
    });
  }

  function buildTimelineChart(data) {
    const canvas = document.getElementById('timelineChart');
    if (!canvas) {
      return;
    }

    timelineChart?.destroy();

    const labels = Object.keys(data || {});
    const values = Object.values(data || {}).map((value) => Number(value));

    timelineChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Tube movements',
          data: values,
          borderColor: rgba('#2563eb', 1),
          backgroundColor: rgba('#2563eb', 0.14),
          tension: 0.25,
          fill: true,
          pointRadius: 3,
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  function buildMapUrl() {
    const base = window.mapPointsUrl;
    if (!base) {
      return null;
    }

    const url = new URL(base, window.location.origin);
    const filters = window.activeFilters || {};

    if (filters.laboratoryFilter) url.searchParams.set('laboratoryFilter', filters.laboratoryFilter);
    if (filters.locationFilter) url.searchParams.set('locationFilter', filters.locationFilter);
    if (filters.boxFilter) url.searchParams.set('boxFilter', filters.boxFilter);
    if (filters.contentTypeFilter) url.searchParams.set('contentTypeFilter', filters.contentTypeFilter);
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
      safety++;
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

  function renderMapLegend(points, key) {
    const legend = document.getElementById('mapLegend');
    if (!legend) {
      return;
    }

    const labels = [...new Set(points.map((point) => pointValue(point, key)))].sort((a, b) => a.localeCompare(b));
    const colors = colorMap(labels);
    renderLegend('mapLegend', labels, colors);
  }

  function buildMap(points) {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || typeof L === 'undefined') {
      return;
    }

    const availableMapOptions = getMapVariableOptions();
    if (!currentMapVariable || !availableMapOptions.some((option) => option.key === currentMapVariable)) {
      currentMapVariable = availableMapOptions[0]?.key || 'content_type';
    }

    applyActiveButtonState('[data-map-tab]', currentMapVariable);

    if (currentMap) {
      currentMap.remove();
      currentMap = null;
    }

    mapContainer.innerHTML = '';
    mapContainer._leaflet_id = null;

    const map = L.map('map', {
      dragging: true,
      touchZoom: true,
      doubleClickZoom: true,
      scrollWheelZoom: true,
      boxZoom: true,
      keyboard: true
    }).setView([-28.5595, 22.9375], 5);

    currentMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const validPoints = (points || []).filter((point) =>
      point.latitude &&
      point.longitude &&
      Number.isFinite(point.latitude) &&
      Number.isFinite(point.longitude)
    );

    const values = validPoints.map((point) => pointValue(point, currentMapVariable));
    const colors = colorMap(values);
    const pointCircles = L.layerGroup().addTo(map);
    const clusterGroup = L.markerClusterGroup({
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
        <strong>Tube:</strong> ${point.code || 'Unknown'}<br>
        <strong>Content type:</strong> ${point.content_type || 'Unknown'}<br>
        <strong>Laboratory:</strong> ${point.laboratory || 'Unknown'}<br>
        <strong>Location:</strong> ${point.location || 'Unknown'}<br>
        <strong>Box:</strong> ${point.box || 'Unknown'}<br>
        <strong>Date moved:</strong> ${point.date_moved || 'Unknown'}<br>
      `;

      const marker = L.marker(latLng, {
        icon: L.divIcon({ html: '', className: 'invisible-marker', iconSize: [1, 1] }),
      });
      marker.pointData = point;
      marker.bindTooltip(tooltip, { sticky: true });
      clusterGroup.addLayer(marker);

      const circle = L.circleMarker(latLng, {
        radius: 7,
        color,
        weight: 2,
        opacity: 0.95,
        fillColor: color,
        fillOpacity: 0.7,
      });
      circle.bindTooltip(tooltip, { sticky: true });
      pointCircles.addLayer(circle);
    });

    map.addLayer(clusterGroup);

    if (validPoints.length) {
      map.fitBounds(L.latLngBounds(validPoints.map((point) => [point.latitude, point.longitude])), {
        padding: [30, 30],
        maxZoom: 8
      });
    }

    renderMapLegend(validPoints, currentMapVariable);
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

  function initializeCharts() {
    if (typeof Chart === 'undefined') {
      return;
    }

    initializeTabHandlers();
    buildPieChart();
    buildBarChart();
    buildTimelineChart(window.timelineData || {});
    loadAllMapPoints();
  }

  function updateStatistics(stats) {
    window.timelineData = (stats && stats.position_timeline) || {};
    buildTimelineChart(window.timelineData);
  }

  function updateCharts(data) {
    window.pieChartTabs = data.pieChartTabs || [];
    window.barChartTabs = data.barChartTabs || [];
    window.mapColorVariableOptions = data.mapColorVariableOptions || [];
    buildPieChart();
    buildBarChart();
  }

  document.addEventListener('DOMContentLoaded', function () {
    initializeCharts();
  });

  document.addEventListener('livewire:init', function () {
    initializeCharts();
  });

  Livewire.on('filtersUpdated', (payload) => {
    const data = payload && payload.data ? payload.data : {};
    window.activeFilters = data.activeFilters || window.activeFilters || {};
    window.mapPointsUrl = data.mapPointsUrl || window.mapPointsUrl || null;
    updateStatistics(data.descriptive_stats || {});
    updateCharts(data);
    loadAllMapPoints();
  });
})();

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

  function ageRangeFromAge(age) {
    if (age === null || age === undefined || Number.isNaN(Number(age))) {
      return 'Unknown';
    }

    const numericAge = Number(age);
    if (numericAge < 18) return '0-17';
    if (numericAge < 30) return '18-29';
    if (numericAge < 50) return '30-49';
    if (numericAge < 70) return '50-69';
    return '70+';
  }

  function pointValue(point, key) {
    if (!point) {
      return 'Unknown';
    }

    if (key === 'age_range') {
      return point.age_range || ageRangeFromAge(point.age);
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
          legend: {
            display: false
          },
          title: {
            display: true,
            text: current?.label || 'Distribution'
          }
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
          legend: {
            display: false
          },
          title: {
            display: true,
            text: current?.label || 'Counts'
          },
          tooltip: {
            callbacks: {
              title(context) {
                return labels[context[0].dataIndex] || '';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          },
          x: {
            ticks: {
              maxRotation: 50,
              minRotation: 0
            }
          }
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
          label: 'Samples collected',
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
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  function buildMapUrl() {
    const base = window.mapPointsUrl;
    if (!base) return null;

    const url = new URL(base, window.location.origin);
    const filters = window.activeFilters || {};

    if (filters.visualize_by) url.searchParams.set('visualize_by', filters.visualize_by);
    if (filters.sampleVisibility) url.searchParams.set('sampleVisibility', filters.sampleVisibility);
    if (filters.sampleTypeFilter) url.searchParams.set('sampleTypeFilter', filters.sampleTypeFilter);
    if (filters.samplingSiteFilter) url.searchParams.set('samplingSiteFilter', filters.samplingSiteFilter);
    if (filters.ethnicityFilter) url.searchParams.set('ethnicityFilter', filters.ethnicityFilter);
    if (filters.occupationFilter) url.searchParams.set('occupationFilter', filters.occupationFilter);
    if (filters.countryFilter) url.searchParams.set('countryFilter', filters.countryFilter);
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
      currentMapVariable = availableMapOptions[0]?.key || 'type';
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
    const clusterGroup = L.markerClusterGroup({
      maxClusterRadius: 50,
      showCoverageOnHover: false,
      iconCreateFunction(cluster) {
        const childMarkers = cluster.getAllChildMarkers();
        const frequencies = {};
        childMarkers.forEach((marker) => {
          const value = marker.options.dashboardValue || 'Unknown';
          frequencies[value] = (frequencies[value] || 0) + 1;
        });

        const dominantValue = Object.entries(frequencies).sort((a, b) => b[1] - a[1])[0]?.[0] || 'Unknown';
        const dominantColor = colors[dominantValue] || '#64748b';

        return L.divIcon({
          className: 'invisible-marker',
          html: `<span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:9999px;background:${rgba(dominantColor, 0.92)};border:2px solid white;color:white;font-size:12px;font-weight:700;box-shadow:0 2px 8px rgba(15,23,42,.3)">${cluster.getChildCount()}</span>`,
          iconSize: [34, 34],
          iconAnchor: [17, 17]
        });
      }
    });

    validPoints.forEach((point) => {
      const value = pointValue(point, currentMapVariable);
      const color = colors[value] || '#64748b';
      const marker = L.marker([point.latitude, point.longitude], {
        dashboardValue: value,
        icon: L.divIcon({
          className: 'invisible-marker',
          html: `<span style="display:inline-block;width:14px;height:14px;border-radius:9999px;background:${rgba(color, 0.92)};border:2px solid white;box-shadow:0 1px 6px rgba(15,23,42,.3)"></span>`,
          iconSize: [14, 14],
          iconAnchor: [7, 7]
        })
      });

      marker.bindTooltip(`
        <strong>Sample code:</strong> ${point.code || 'Unknown'}<br>
        <strong>Sample type:</strong> ${point.type || 'Unknown'}<br>
        <strong>Sampling site:</strong> ${point.sampling_site || 'Unknown'}<br>
        <strong>Ethnicity:</strong> ${point.ethnicity || 'Unknown'}<br>
        <strong>Occupation:</strong> ${point.occupation || 'Unknown'}<br>
        <strong>Country:</strong> ${point.country || 'Unknown'}<br>
        <strong>Sex:</strong> ${point.sex || 'Unknown'}<br>
        <strong>Age range:</strong> ${point.age_range || ageRangeFromAge(point.age)}<br>
      `, { sticky: true });

      clusterGroup.addLayer(marker);
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

  window.loadHumanDashboardModal = function (modalId) {
    const url = window.modalTableUrls ? window.modalTableUrls[modalId] : null;
    if (!url) return;

    const contentId = `${modalId}Content`;
    const el = document.getElementById(contentId);
    if (!el) return;

    const u = new URL(url, window.location.origin);
    const f = window.activeFilters || {};
    if (f.sampleVisibility) u.searchParams.set('sampleVisibility', f.sampleVisibility);
    if (f.sampleTypeFilter) u.searchParams.set('sampleTypeFilter', f.sampleTypeFilter);
    if (f.samplingSiteFilter) u.searchParams.set('samplingSiteFilter', f.samplingSiteFilter);
    if (f.ethnicityFilter) u.searchParams.set('ethnicityFilter', f.ethnicityFilter);
    if (f.occupationFilter) u.searchParams.set('occupationFilter', f.occupationFilter);
    if (f.countryFilter) u.searchParams.set('countryFilter', f.countryFilter);
    if (f.subProjectFilter) u.searchParams.set('subProjectFilter', f.subProjectFilter);
    if (f.startDate) u.searchParams.set('startDate', f.startDate);
    if (f.endDate) u.searchParams.set('endDate', f.endDate);

    el.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => response.text())
      .then((html) => {
        el.innerHTML = html;
        window.enhanceDashboardModalTable?.(el);
      })
      .catch(() => {
        el.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  };

  document.addEventListener('click', function (event) {
    const link = event.target && event.target.closest ? event.target.closest('#humanSamplesModalContent nav a[href]') : null;
    if (!link) {
      return;
    }

    event.preventDefault();
    const modalId = 'humanSamplesModal';
    const el = document.getElementById(`${modalId}Content`);
    if (!el) {
      return;
    }

    const u = new URL(link.getAttribute('href'), window.location.origin);
    const f = window.activeFilters || {};
    if (f.sampleVisibility) u.searchParams.set('sampleVisibility', f.sampleVisibility);
    if (f.sampleTypeFilter) u.searchParams.set('sampleTypeFilter', f.sampleTypeFilter);
    if (f.samplingSiteFilter) u.searchParams.set('samplingSiteFilter', f.samplingSiteFilter);
    if (f.ethnicityFilter) u.searchParams.set('ethnicityFilter', f.ethnicityFilter);
    if (f.occupationFilter) u.searchParams.set('occupationFilter', f.occupationFilter);
    if (f.countryFilter) u.searchParams.set('countryFilter', f.countryFilter);
    if (f.subProjectFilter) u.searchParams.set('subProjectFilter', f.subProjectFilter);
    if (f.startDate) u.searchParams.set('startDate', f.startDate);
    if (f.endDate) u.searchParams.set('endDate', f.endDate);

    el.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => response.text())
      .then((html) => {
        el.innerHTML = html;
        window.enhanceDashboardModalTable?.(el);
      })
      .catch(() => {
        el.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  });

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
    window.modalTableUrls = data.modalTableUrls || window.modalTableUrls || {};
    updateStatistics(data.descriptive_stats || {});
    updateCharts(data);
    loadAllMapPoints();
  });
})();

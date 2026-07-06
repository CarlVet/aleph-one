(function () {
  'use strict';

  // Define source colors for sequences (by origin type)
  const sourceColors = {
    HumanSamples: 'rgba(185, 28, 28, 0.8)', // Dark red
    AnimalSamples: 'rgba(34, 197, 94, 0.8)', // Green
    EnvironmentSamples: 'rgba(147, 51, 234, 0.8)', // Purple
    ParasiteSamples: 'rgba(249, 115, 22, 0.8)', // Orange
    Cultures: 'rgba(234, 179, 8, 0.8)', // Yellow
    Pools: 'rgba(99, 102, 241, 0.8)', // Indigo
  };

  // Global map variable
  let currentMap = null;

  function enableTrackpadPan(map) {
    if (!map || !map.getContainer) return;
    const container = map.getContainer();
    map.scrollWheelZoom?.disable();

    container.addEventListener('wheel', function(e) {
      if (!map) return;
      if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        const dir = e.deltaY > 0 ? -1 : 1;
        const nextZoom = map.getZoom() + dir;
        const point = map.mouseEventToContainerPoint(e);
        map.setZoomAround(point, nextZoom);
        return;
      }

      e.preventDefault();
      e.stopPropagation();
      map.panBy([e.deltaX, e.deltaY], { animate: false });
    }, { passive: false });
  }

  function enableMouseDragPan(map) {
    if (!map || !map.getContainer) return;
    const container = map.getContainer();

    let isDown = false;
    let lastX = 0;
    let lastY = 0;

    container.addEventListener('mousedown', (e) => {
      if (e.button !== 0) return;
      if (e.target && e.target.closest && e.target.closest('.leaflet-control')) return;
      isDown = true;
      lastX = e.clientX;
      lastY = e.clientY;
      container.classList.add('leaflet-mouse-panning');
      e.preventDefault();
    }, { capture: true });

    document.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      const dx = e.clientX - lastX;
      const dy = e.clientY - lastY;
      lastX = e.clientX;
      lastY = e.clientY;
      map.panBy([dx, dy], { animate: false });
    });

    document.addEventListener('mouseup', () => {
      if (!isDown) return;
      isDown = false;
      container.classList.remove('leaflet-mouse-panning');
    });
  }

  // Create pie chart for cluster icon
  function createPieChartForCluster(sourceCounts, totalCount) {
    const size = 40;
    const radius = 16;
    const centerX = 20;
    const centerY = 20;

    const activeSources = Object.entries(sourceCounts)
      .filter(([, count]) => count > 0)
      .map(([source, count]) => ({
        source,
        count,
        color: sourceColors[source] || '#gray',
      }));

    if (activeSources.length === 0) {
      return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
        <circle cx="${centerX}" cy="${centerY}" r="${radius}" fill="none" stroke="#ccc" stroke-width="2"/>
        <text x="${centerX}" y="${centerY + 4}" text-anchor="middle" font-size="12" font-weight="bold" fill="#666">${totalCount}</text>
      </svg>`;
    }

    if (activeSources.length === 1) {
      const source = activeSources[0];
      return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
        <circle cx="${centerX}" cy="${centerY}" r="${radius}" fill="${source.color}" stroke="white" stroke-width="2"/>
        <text x="${centerX}" y="${centerY + 4}" text-anchor="middle" font-size="12" font-weight="bold" fill="white">${totalCount}</text>
      </svg>`;
    }

    let currentAngle = 0;
    const total = activeSources.reduce((sum, item) => sum + item.count, 0);

    let svgPaths = '';

    activeSources.forEach((item) => {
      const angle = (item.count / total) * 2 * Math.PI;
      const endAngle = currentAngle + angle;

      const x1 = centerX + radius * Math.cos(currentAngle);
      const y1 = centerY + radius * Math.sin(currentAngle);
      const x2 = centerX + radius * Math.cos(endAngle);
      const y2 = centerY + radius * Math.sin(endAngle);

      const largeArcFlag = angle > Math.PI ? 1 : 0;

      const pathData = `M ${centerX} ${centerY} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} Z`;

      svgPaths += `<path d="${pathData}" fill="${item.color}" stroke="white" stroke-width="1"/>`;

      currentAngle = endAngle;
    });

    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
      <circle cx="${centerX}" cy="${centerY}" r="${radius + 1}" fill="white" stroke="#ddd" stroke-width="1"/>
      ${svgPaths}
      <circle cx="${centerX}" cy="${centerY}" r="6" fill="white" stroke="#333" stroke-width="1.5"/>
      <text x="${centerX}" y="${centerY + 4}" text-anchor="middle" font-size="8" font-weight="bold" fill="#333">${totalCount}</text>
    </svg>`;
  }

  // Helper function to create tooltip content for clusters
  function createClusterTooltipContent(samples, pieChartData) {
    let tooltip = `<div style="font-size: 12px;"><strong>Location: ${samples[0].latitude.toFixed(
      4
    )}, ${samples[0].longitude.toFixed(4)}</strong><br>`;
    tooltip += `<strong>Total sequences: ${samples.length}</strong><br><br>`;

    tooltip += `<strong>Origin distribution:</strong><br>`;
    pieChartData.forEach((item) => {
      const percentage = ((item.value / samples.length) * 100).toFixed(1);
      tooltip += `<span style="color: ${item.color}">●</span> ${item.label}: ${item.value} (${percentage}%)<br>`;
    });

    tooltip += `<br><strong>Sequence codes:</strong><br>`;
    samples.slice(0, 8).forEach((sample) => {
      tooltip += `• ${sample.code || 'Unknown'} (${sample.source_type || 'Unknown'}) - ${
        sample.method || 'Unknown'
      }<br>`;
    });

    if (samples.length > 8) {
      tooltip += `... and ${samples.length - 8} more`;
    }

    tooltip += `</div>`;
    return tooltip;
  }

  Livewire.on('filtersUpdated', (payload) => {
    console.log('Filters updated, payload:', payload);
    window.mapPointsUrl = payload.data.mapPointsUrl || window.mapPointsUrl;
    window.activeFilters = payload.data.activeFilters || window.activeFilters || {};
    window.modalTableUrls = payload.data.modalTableUrls || window.modalTableUrls || {};

    updateStatistics(payload.data.descriptive_stats);
    updateCharts(payload.data);
    loadAllMapPoints();
  });

  function updateStatistics(stats) {
    buildTimelineChart(stats.sequencing_timeline);
  }

  function updateCharts(data) {
    buildSourceChart(data.sequencesBySource);
    buildMethodsChart(data.sequencesByMethod);
    buildInstrumentsChart(data.sequencesByInstrument);
    buildLaboratoriesChart(data.sequencesByLaboratory);
  }

  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initializeCharts, 500);
  });

  document.addEventListener('livewire:initialized', function () {
    setTimeout(initializeCharts, 500);
  });

  document.addEventListener('livewire:update', function () {
    setTimeout(initializeCharts, 500);
  });

  window.addEventListener('load', function () {
    setTimeout(initializeCharts, 500);
  });

  function initializeCharts() {
    if (typeof Chart === 'undefined') {
      console.error('Chart.js is not loaded!');
      return;
    }

    const timelineData = window.timelineData || {};
    const sequencesBySource = window.sequencesBySource || {};
    const sequencesByMethod = window.sequencesByMethod || {};
    const sequencesByInstrument = window.sequencesByInstrument || {};
    const sequencesByLaboratory = window.sequencesByLaboratory || {};

    if (Object.keys(sequencesBySource).length > 0) {
      buildSourceChart(sequencesBySource);
    }

    if (Object.keys(sequencesByMethod).length > 0) {
      buildMethodsChart(sequencesByMethod);
    }

    if (Object.keys(sequencesByInstrument).length > 0) {
      buildInstrumentsChart(sequencesByInstrument);
    }

    if (Object.keys(sequencesByLaboratory).length > 0) {
      buildLaboratoriesChart(sequencesByLaboratory);
    }

    if (Object.keys(timelineData).length > 0) {
      buildTimelineChart(timelineData);
    }

    loadAllMapPoints();
  }

  function buildMapUrl() {
    const base = window.mapPointsUrl;
    if (!base) return null;

    const url = new URL(base, window.location.origin);
    const f = window.activeFilters || {};

    if (f.sourceTypeFilter) url.searchParams.set('sourceTypeFilter', f.sourceTypeFilter);
    if (f.methodFilter) url.searchParams.set('methodFilter', f.methodFilter);
    if (f.instrumentFilter) url.searchParams.set('instrumentFilter', f.instrumentFilter);
    if (f.laboratoryFilter) url.searchParams.set('laboratoryFilter', f.laboratoryFilter);
    if (f.sequencedByFilter) url.searchParams.set('sequencedByFilter', f.sequencedByFilter);
    if (f.subProjectFilter) url.searchParams.set('subProjectFilter', f.subProjectFilter);
    if (f.startLength) url.searchParams.set('startLength', String(f.startLength));
    if (f.endLength) url.searchParams.set('endLength', String(f.endLength));
    if (f.startDate) url.searchParams.set('startDate', f.startDate);
    if (f.endDate) url.searchParams.set('endDate', f.endDate);

    return url.toString();
  }

  async function loadAllMapPoints() {
    const baseUrl = buildMapUrl();
    if (!baseUrl) return;

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

      const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
      if (!res.ok) break;
      const data = await res.json();
      const points = Array.isArray(data.points) ? data.points : [];
      all.push(...points);
      cursor = data.next_cursor;
      if (!cursor || points.length === 0) break;
    }

    buildMap(all);
  }

  window.loadSequencesDashboardModal = function (modalId) {
    const url = window.modalTableUrls ? window.modalTableUrls[modalId] : null;
    if (!url) return;

    const el = document.getElementById(`${modalId}Content`);
    if (!el) return;

    const u = new URL(url, window.location.origin);
    const f = window.activeFilters || {};

    if (f.sourceTypeFilter) u.searchParams.set('sourceTypeFilter', f.sourceTypeFilter);
    if (f.methodFilter) u.searchParams.set('methodFilter', f.methodFilter);
    if (f.instrumentFilter) u.searchParams.set('instrumentFilter', f.instrumentFilter);
    if (f.laboratoryFilter) u.searchParams.set('laboratoryFilter', f.laboratoryFilter);
    if (f.sequencedByFilter) u.searchParams.set('sequencedByFilter', f.sequencedByFilter);
    if (f.subProjectFilter) u.searchParams.set('subProjectFilter', f.subProjectFilter);
    if (f.startLength) u.searchParams.set('startLength', String(f.startLength));
    if (f.endLength) u.searchParams.set('endLength', String(f.endLength));
    if (f.startDate) u.searchParams.set('startDate', f.startDate);
    if (f.endDate) u.searchParams.set('endDate', f.endDate);

    el.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.text())
      .then((html) => {
        el.innerHTML = html;
        window.enhanceDashboardModalTable?.(el);
      })
      .catch(() => {
        el.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  };

  document.addEventListener('click', function (e) {
    const link = e.target && e.target.closest ? e.target.closest('[data-modal-content] nav a[href]') : null;
    if (!link) return;

    const container = link.closest('[data-modal-content]');
    if (!container) return;
    e.preventDefault();

    const u = new URL(link.getAttribute('href'), window.location.origin);
    const f = window.activeFilters || {};

    if (f.sourceTypeFilter) u.searchParams.set('sourceTypeFilter', f.sourceTypeFilter);
    if (f.methodFilter) u.searchParams.set('methodFilter', f.methodFilter);
    if (f.instrumentFilter) u.searchParams.set('instrumentFilter', f.instrumentFilter);
    if (f.laboratoryFilter) u.searchParams.set('laboratoryFilter', f.laboratoryFilter);
    if (f.sequencedByFilter) u.searchParams.set('sequencedByFilter', f.sequencedByFilter);
    if (f.subProjectFilter) u.searchParams.set('subProjectFilter', f.subProjectFilter);
    if (f.startLength) u.searchParams.set('startLength', String(f.startLength));
    if (f.endLength) u.searchParams.set('endLength', String(f.endLength));
    if (f.startDate) u.searchParams.set('startDate', f.startDate);
    if (f.endDate) u.searchParams.set('endDate', f.endDate);

    container.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
    fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((r) => r.text())
      .then((html) => {
        container.innerHTML = html;
        window.enhanceDashboardModalTable?.(container);
      })
      .catch(() => {
        container.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>';
      });
  });

  function buildMap(samples) {
    console.log('Building map with samples:', samples);

    const mapContainer = document.getElementById('map');
    if (!mapContainer) {
      console.error('Map container not found');
      return;
    }

    if (currentMap) {
      try {
        currentMap.remove();
      } catch (e) {
        console.log('Error removing existing map:', e);
      }
      currentMap = null;
    }

    mapContainer.innerHTML = '';
    mapContainer._leaflet_id = null;

    setTimeout(() => {
      try {
        const map = L.map('map', {
          dragging: true,
          touchZoom: true,
          doubleClickZoom: true,
          scrollWheelZoom: true,
          boxZoom: true,
          keyboard: true,
          tap: true,
          tapTolerance: 15,
        }).setView([-28.5595, 22.9375], 5);
        currentMap = map;

        map.dragging?.enable();
        map.touchZoom?.enable();
        map.doubleClickZoom?.enable();
        map.scrollWheelZoom?.enable();
        map.boxZoom?.enable();
        map.keyboard?.enable();
        map.tap?.enable?.();
        enableTrackpadPan(map);
        enableMouseDragPan(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© OpenStreetMap contributors',
        }).addTo(map);

        const markers = L.markerClusterGroup({
          iconCreateFunction: function (cluster) {
            const childMarkers = cluster.getAllChildMarkers();
            const sourceCounts = {
              HumanSamples: 0,
              AnimalSamples: 0,
              EnvironmentSamples: 0,
              ParasiteSamples: 0,
              Cultures: 0,
              Pools: 0,
            };

            childMarkers.forEach((marker) => {
              if (marker.sampleData && marker.sampleData.source_type) {
                const source = marker.sampleData.source_type;
                if (Object.prototype.hasOwnProperty.call(sourceCounts, source)) {
                  sourceCounts[source]++;
                }
              }
            });

            const totalCount = cluster.getChildCount();
            const pieChartSvg = createPieChartForCluster(sourceCounts, totalCount);

            return L.divIcon({
              html: pieChartSvg,
              className: 'pie-chart-cluster-icon',
              iconSize: [40, 40],
              iconAnchor: [20, 20],
            });
          },
          maxClusterRadius: 50,
          spiderfyOnMaxZoom: true,
          showCoverageOnHover: false,
          zoomToBoundsOnClick: true,
        });

        samples.forEach((sample) => {
          if (sample.latitude && sample.longitude && isFinite(sample.latitude) && isFinite(sample.longitude)) {
            const latLng = [sample.latitude, sample.longitude];

            const marker = L.marker(latLng, {
              icon: L.divIcon({
                html: '',
                className: 'invisible-marker',
                iconSize: [1, 1],
              }),
            });

            marker.sampleData = sample;

            marker.bindTooltip(
              `<strong>Sequence:</strong> ${sample.code || 'Unknown'}<br>
               <strong>Nucleic acid:</strong> ${sample.nucleic_code || 'Unknown'}<br>
               <strong>Origin:</strong> ${sample.source_type || 'Unknown'}<br>
               <strong>Method:</strong> ${sample.method || 'Unknown'}<br>
               <strong>Instrument:</strong> ${sample.instrument || 'Unknown'}<br>`,
              { sticky: true }
            );

            markers.addLayer(marker);

            const source = sample.source_type || 'Unknown';
            const circle = L.circle(latLng, {
              color: sourceColors[source] || 'gray',
              fillColor: sourceColors[source] || 'gray',
              radius: 100,
              weight: 5,
              fillOpacity: 0.4,
              interactive: false,
            }).addTo(map);

            circle.on('mouseover', function (e) {
              const layer = e.target;
              layer.setStyle({
                color: 'cyan',
                fillColor: 'cyan',
              });
              layer
                .bindTooltip(
                  `<strong>Sequence:</strong> ${sample.code || 'Unknown'}<br>
                   <strong>Nucleic acid:</strong> ${sample.nucleic_code || 'Unknown'}<br>
                   <strong>Origin:</strong> ${sample.source_type || 'Unknown'}<br>
                   <strong>Method:</strong> ${sample.method || 'Unknown'}<br>
                   <strong>Instrument:</strong> ${sample.instrument || 'Unknown'}<br>`,
                  { sticky: true }
                )
                .openTooltip();
            });

            circle.on('mouseout', function (e) {
              const layer = e.target;
              layer.setStyle({
                color: sourceColors[source] || 'gray',
                fillColor: sourceColors[source] || 'gray',
              });
              layer.closeTooltip();
            });
          }
        });

        map.addLayer(markers);

        if (!samples || samples.length === 0) {
          console.log('No sequences with location data, setting default view');
          map.setView([-28.5595, 22.9375], 5);
        }

        const loadGeoJsonLayer = (url, styleOptions, hoverTitle) => {
          fetch(url)
            .then((response) => response.json())
            .then((geojson) => {
              L.geoJSON(geojson, {
                style: styleOptions,
                onEachFeature: (feature, layer) => {
                  layer.on('mouseover', function (e) {
                    const hovered = e.target;
                    hovered.setStyle({ color: 'cyan', weight: 3, opacity: 1 });
                    hovered
                      .bindTooltip(hoverTitle, {
                        sticky: true,
                        direction: 'auto',
                        className: 'polygon-hover-label',
                      })
                      .openTooltip();
                  });
                  layer.on('mouseout', function (e) {
                    const hovered = e.target;
                    hovered.setStyle(styleOptions);
                    hovered.closeTooltip();
                  });
                },
              }).addTo(map);
            })
            .catch((error) => {
              console.log('Error loading GeoJSON:', error);
            });
        };

        loadGeoJsonLayer(
          '/shapefiles/Lapalala.geojson',
          {
            color: 'green',
            weight: 2,
            opacity: 0.6,
          },
          'Lapalala Nature Reserve'
        );

        loadGeoJsonLayer(
          '/shapefiles/Sanparks.geojson',
          {
            color: 'green',
            weight: 2,
            opacity: 0.6,
          },
          'SANParks Protected Area'
        );

        loadGeoJsonLayer(
          '/shapefiles/Kruger.geojson',
          {
            color: 'green',
            weight: 2,
            opacity: 0.6,
          },
          'Kruger National Park'
        );

        markers.on('clustermouseover', function (a) {
          const childMarkers = a.layer.getAllChildMarkers();
          const samples = childMarkers.map((m) => m.sampleData).filter(Boolean);

          if (samples.length === 0) return;

          const sourceCounts = {};
          samples.forEach((s) => {
            const src = s.source_type || 'Unknown';
            sourceCounts[src] = (sourceCounts[src] || 0) + 1;
          });

          const pieChartData = Object.entries(sourceCounts).map(([label, value]) => ({
            label,
            value,
            color: sourceColors[label] || 'gray',
          }));

          const tooltipContent = createClusterTooltipContent(samples, pieChartData);
          a.layer.bindTooltip(tooltipContent, { sticky: true }).openTooltip();
        });

        markers.on('clustermouseout', function (a) {
          a.layer.closeTooltip();
        });
      } catch (error) {
        console.error('Error creating map:', error);
      }
    }, 100);
  }

  function buildSourceChart(sequencesBySource) {
    const ctx = document.getElementById('sourceChart');
    if (!ctx) return;

    Chart.getChart(ctx)?.destroy();

    const labels = Object.keys(sequencesBySource);
    const data = Object.values(sequencesBySource);

    const colorMapping = {
      Human: 'HumanSamples',
      Animal: 'AnimalSamples',
      Environment: 'EnvironmentSamples',
      Parasite: 'ParasiteSamples',
      Culture: 'Cultures',
      Pool: 'Pools',
    };

    // eslint-disable-next-line no-new
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [
          {
            data,
            backgroundColor: labels.map(
              (key) => sourceColors[colorMapping[key]] || 'rgba(128, 128, 128, 0.8)'
            ),
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
    });
  }

  function generateAcronym(label) {
    if (!label) return 'Unknown';

    const words = label.trim().split(/\s+/);
    if (words.length === 1) {
      return label.substring(0, Math.min(4, label.length)).toUpperCase();
    }

    return words.map((w) => w.charAt(0)).join('').toUpperCase().substring(0, 4);
  }

  function buildBarChart(canvasId, title, dataMap, color) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    Chart.getChart(ctx)?.destroy();

    const labels = Object.keys(dataMap);
    const data = Object.values(dataMap);
    const acronymLabels = labels.map((label) => generateAcronym(label));

    // eslint-disable-next-line no-new
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: acronymLabels,
        datasets: [
          {
            label: title,
            data,
            backgroundColor: color,
            borderColor: color.replace('0.8', '1'),
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: function (context) {
                const dataIndex = context[0].dataIndex;
                return labels[dataIndex];
              },
            },
          },
        },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } },
          x: { ticks: { maxRotation: 45 } },
        },
      },
    });
  }

  function buildMethodsChart(sequencesByMethod) {
    buildBarChart('methodsChart', 'Sequences', sequencesByMethod, 'rgba(59, 130, 246, 0.8)');
  }

  function buildInstrumentsChart(sequencesByInstrument) {
    buildBarChart('instrumentsChart', 'Sequences', sequencesByInstrument, 'rgba(245, 158, 11, 0.8)');
  }

  function buildLaboratoriesChart(sequencesByLaboratory) {
    buildBarChart('laboratoriesChart', 'Sequences', sequencesByLaboratory, 'rgba(147, 51, 234, 0.8)');
  }

  function buildTimelineChart(timelineData) {
    const ctx = document.getElementById('timelineChart');
    if (!ctx) return;

    Chart.getChart(ctx)?.destroy();

    const labels = Object.keys(timelineData);
    const data = Object.values(timelineData);

    // eslint-disable-next-line no-new
    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Sequences',
            data,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(75, 192, 192, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } },
          x: { ticks: { maxRotation: 45 } },
        },
      },
    });
  }
})();


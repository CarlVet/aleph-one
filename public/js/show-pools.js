(function () {
  'use strict';

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

  Livewire.on('filtersUpdated', (payload) => {
    if (payload?.data?.mapPointsUrl) {
      window.mapPointsUrl = payload.data.mapPointsUrl;
    }
    if (payload?.data?.activeFilters) {
      window.activeFilters = payload.data.activeFilters;
    }

    updateStatistics(payload.data.descriptive_stats);
    updateCharts(payload.data);
    loadAllMapPoints();
  });

  function updateStatistics(stats) {
    buildTimelineChart(stats.pooling_timeline);
  }

  function updateCharts(data) {
    if (data.poolsByLaboratory) {
      buildLaboratoriesChart(data.poolsByLaboratory);
    }
  }

  // Initialize on load
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initialize, 250);
  });
  document.addEventListener('livewire:initialized', function () {
    setTimeout(initialize, 250);
  });
  document.addEventListener('livewire:update', function () {
    setTimeout(initialize, 250);
  });
  window.addEventListener('load', function () {
    setTimeout(initialize, 250);
  });

  function initialize() {
    if (typeof Chart !== 'undefined') {
      if (window.poolsByLaboratory) {
        buildLaboratoriesChart(window.poolsByLaboratory);
      }
      if (window.timelineData) {
        buildTimelineChart(window.timelineData);
      }
    }

    loadAllMapPoints();
  }

  function buildMapUrl() {
    const base = window.mapPointsUrl;
    if (!base) return null;

    const url = new URL(base, window.location.origin);
    const f = window.activeFilters || {};

    if (f.contentTypeFilter) url.searchParams.set('contentTypeFilter', f.contentTypeFilter);
    if (f.tracePrimaryTypeFilter) url.searchParams.set('tracePrimaryTypeFilter', f.tracePrimaryTypeFilter);
    if (f.tracePrimaryAnimalSpeciesFilter) url.searchParams.set('tracePrimaryAnimalSpeciesFilter', f.tracePrimaryAnimalSpeciesFilter);
    if (f.minNrPooled !== null && typeof f.minNrPooled !== 'undefined' && f.minNrPooled !== '') {
      url.searchParams.set('minNrPooled', String(f.minNrPooled));
    }
    if (f.maxNrPooled !== null && typeof f.maxNrPooled !== 'undefined' && f.maxNrPooled !== '') {
      url.searchParams.set('maxNrPooled', String(f.maxNrPooled));
    }
    if (f.laboratoryFilter) url.searchParams.set('laboratoryFilter', f.laboratoryFilter);
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

  function buildMap(points) {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Clear any previous map instance
    let container = L.DomUtil.get('map');
    if (container != null) {
      container._leaflet_id = null;
    }
    if (currentMap) {
      try {
        currentMap.remove();
      } catch (_) {}
      currentMap = null;
    }

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
        return L.divIcon({
          html: `<div class="custom-cluster-icon"><span>${cluster.getChildCount()}</span></div>`,
          className: 'custom-cluster-icon',
          iconSize: [30, 30],
        });
      },
    });

    (points || []).forEach((p) => {
      if (!p || !p.latitude || !p.longitude) return;
      if (!isFinite(p.latitude) || !isFinite(p.longitude)) return;

      const latLng = [p.latitude, p.longitude];

      const marker = L.marker(latLng);
      marker.bindTooltip(
        `<strong>Pool:</strong> ${p.code || 'Unknown'}<br>
         <strong>Pooled:</strong> ${p.nr_pooled ?? 'N/A'}<br>
         <strong>Date:</strong> ${p.date_pooled || 'N/A'}<br>
         <strong>Laboratory:</strong> ${p.laboratory || 'Unknown'}<br>`,
        { sticky: true }
      );
      markers.addLayer(marker);

      L.circle(latLng, {
        color: 'rgba(99, 102, 241, 0.9)',
        fillColor: 'rgba(99, 102, 241, 0.6)',
        radius: 80,
        weight: 2,
        fillOpacity: 0.35,
        interactive: false,
      }).addTo(map);
    });

    map.addLayer(markers);
  }

  function buildLaboratoriesChart(poolsByLaboratory) {
    const ctxEl = document.getElementById('laboratoriesChart');
    if (!ctxEl) return;

    Chart.getChart(ctxEl)?.destroy();

    const labels = Object.keys(poolsByLaboratory || {});
    const data = Object.values(poolsByLaboratory || {});

    new Chart(ctxEl, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Pools',
            data,
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderColor: 'rgba(99, 102, 241, 1)',
            borderWidth: 1,
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

  function buildTimelineChart(timelineData) {
    const ctxEl = document.getElementById('timelineChart');
    if (!ctxEl) return;

    Chart.getChart(ctxEl)?.destroy();

    const labels = Object.keys(timelineData || {});
    const data = Object.values(timelineData || {});

    new Chart(ctxEl, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Pools created',
            data,
            borderColor: 'rgba(16, 185, 129, 1)',
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
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


(function () {
    'use strict';

    if (window.__microplasticsDashboardScriptLoaded) {
        return;
    }
    window.__microplasticsDashboardScriptLoaded = true;

    const BASE_COLORS = ['#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#84cc16', '#f97316'];
    const SOURCE_COLORS = {
        HumanSamples: '#b91c1c',
        AnimalSamples: '#16a34a',
        EnvironmentSamples: '#7c3aed',
        ParasiteSamples: '#ea580c',
        Cultures: '#ca8a04',
        Pools: '#4f46e5',
        NucleicAcids: '#0891b2',
    };

    let pieChart = null;
    let barChart = null;
    let timelineChart = null;
    let currentMap = null;
    let currentClusters = null;
    let currentMapColorVariable = 'source';
    let rawMapPoints = [];
    let mapLoadToken = 0;

    function normalizeValue(value) {
        const stringValue = String(value ?? '').trim();
        return stringValue === '' ? 'Unknown' : stringValue;
    }

    function valueColor(variable, value) {
        const normalized = normalizeValue(value);
        if (variable === 'source') {
            return SOURCE_COLORS[normalized] || '#64748b';
        }

        const key = `${variable}:${normalized}`;
        let hash = 0;
        for (let i = 0; i < key.length; i++) {
            hash = ((hash << 5) - hash) + key.charCodeAt(i);
            hash |= 0;
        }

        return BASE_COLORS[Math.abs(hash) % BASE_COLORS.length];
    }

    function bucketKeyForPoint(point, variable) {
        switch (variable) {
            case 'source':
                return normalizeValue(point.source_type);
            case 'mps_type':
                return normalizeValue(point.mps_type);
            case 'protocol':
                return normalizeValue(point.protocol);
            case 'laboratory':
                return normalizeValue(point.laboratory);
            case 'identified_by':
                return normalizeValue(point.identified_by);
            default:
                return normalizeValue(point.source_type);
        }
    }

    function buildDistribution(points, variable) {
        const counts = {};
        (points || []).forEach((point) => {
            const key = bucketKeyForPoint(point, variable);
            if (key === 'Unknown') {
                return;
            }
            counts[key] = (counts[key] || 0) + 1;
        });
        return counts;
    }

    function setActiveTabButtons(selector, activeKey, dataAttr) {
        document.querySelectorAll(selector).forEach((button) => {
            button.classList.toggle('is-active', button.getAttribute(dataAttr) === activeKey);
        });
    }

    function renderPieLegend(labels, colors, values) {
        const container = document.getElementById('pieLegendScroller');
        if (!container) {
            return;
        }

        container.innerHTML = labels.length === 0 ? '' : `
            <div class="inline-flex w-max min-w-full items-center gap-4 pb-1 pr-3">
                ${labels.map((label, index) => `
                    <div class="inline-flex items-center gap-2 text-xs text-gray-700">
                        <span class="w-3 h-3 rounded-full" style="background:${colors[index]}"></span>
                        <span class="whitespace-nowrap">${label} (${values[index] ?? 0})</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function tabDataByKey(tabs, key) {
        return (tabs || []).find((tab) => tab.key === key) || tabs?.[0] || { key: '', label: '', data: {} };
    }

    function renderPieTabbedChart(tabKey) {
        const selected = tabDataByKey(window.microplasticsPieChartTabs || [], tabKey);
        const canvas = document.getElementById('pieTabbedChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const labels = Object.keys(selected.data || {});
        const values = Object.values(selected.data || {});
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (pieChart) {
            pieChart.destroy();
        }

        pieChart = new Chart(canvas, {
            type: 'pie',
            data: {
                labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });

        renderPieLegend(labels, colors, values);
        setActiveTabButtons('[data-pie-tab]', selected.key, 'data-pie-tab');
    }

    function renderBarTabbedChart(tabKey) {
        const selected = tabDataByKey(window.microplasticsBarChartTabs || [], tabKey);
        const canvas = document.getElementById('barTabbedChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const entries = Object.entries(selected.data || {}).sort((a, b) => b[1] - a[1]).slice(0, 20);
        const labels = entries.map(([label]) => label);
        const values = entries.map(([, count]) => count);
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (barChart) {
            barChart.destroy();
        }

        barChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: selected.label || 'Count',
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 1,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { maxRotation: 45, minRotation: 10 } },
                },
                plugins: { legend: { display: false } },
            },
        });

        setActiveTabButtons('[data-bar-tab]', selected.key, 'data-bar-tab');
    }

    function renderTimelineChart(data) {
        const canvas = document.getElementById('timelineChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: Object.keys(data || {}),
                datasets: [{
                    label: 'Identifications',
                    data: Object.values(data || {}),
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.18)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            },
        });
    }

    function refreshLegend(points, variable) {
        const legend = document.getElementById('mapLegend');
        if (!legend) {
            return;
        }

        const distribution = buildDistribution(points, variable);
        const entries = Object.entries(distribution).sort((a, b) => b[1] - a[1]).slice(0, 12);

        legend.innerHTML = entries.length === 0
            ? '<span class="text-gray-500">No data available for legend.</span>'
            : entries.map(([label, count]) => `
                <div class="inline-flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background:${valueColor(variable, label)}"></span>
                    <span>${label}</span>
                    <span class="text-gray-400">(${count})</span>
                </div>
            `).join('');
    }

    function createPieChartSvg(distribution, totalCount) {
        const size = 42;
        const radius = 17;
        const cx = 21;
        const cy = 21;
        const slices = Object.entries(distribution)
            .filter(([, count]) => count > 0)
            .map(([label, count]) => ({ label, count, color: valueColor(currentMapColorVariable, label) }));

        if (slices.length === 0) {
            return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
                <circle cx="${cx}" cy="${cy}" r="${radius}" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"></circle>
                <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
            </svg>`;
        }

        if (slices.length === 1) {
            return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
                <circle cx="${cx}" cy="${cy}" r="${radius}" fill="${slices[0].color}" stroke="${slices[0].color}" stroke-width="1"></circle>
                <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
            </svg>`;
        }

        let currentAngle = -Math.PI / 2;
        const total = slices.reduce((sum, slice) => sum + slice.count, 0);
        let paths = '';

        slices.forEach((slice, index) => {
            const angle = (slice.count / total) * 2 * Math.PI;
            const endAngle = index === slices.length - 1 ? ((3 * Math.PI) / 2) : (currentAngle + angle);
            const x1 = cx + radius * Math.cos(currentAngle);
            const y1 = cy + radius * Math.sin(currentAngle);
            const x2 = cx + radius * Math.cos(endAngle);
            const y2 = cy + radius * Math.sin(endAngle);
            const largeArc = angle > Math.PI ? 1 : 0;
            const path = `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`;
            paths += `<path d="${path}" fill="${slice.color}" stroke="${slice.color}" stroke-width="0.8"></path>`;
            currentAngle = endAngle;
        });

        return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
            ${paths}
            <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
        </svg>`;
    }

    function buildUrlWithFilters(base) {
        if (!base) {
            return null;
        }

        const url = new URL(base, window.location.origin);
        const filters = window.microplasticsActiveFilters || {};
        Object.entries(filters).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                return;
            }
            url.searchParams.set(key, String(value));
        });
        return url;
    }

    function drawMap(points) {
        const mapContainer = document.getElementById('map');
        if (!mapContainer || typeof L === 'undefined') {
            return;
        }

        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');

        if (currentMap) {
            try {
                currentMap.remove();
            } catch (_) {
                // no-op
            }
            currentMap = null;
        }

        mapContainer.innerHTML = '';
        mapContainer._leaflet_id = null;

        currentMap = L.map('map').setView([-28.5595, 22.9375], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
            crossOrigin: true,
        }).addTo(currentMap);

        const clusters = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            iconCreateFunction(cluster) {
                const clusterPoints = cluster.getAllChildMarkers().map((marker) => marker.sampleData).filter(Boolean);
                let distribution = buildDistribution(clusterPoints, currentMapColorVariable);
                if (Object.keys(distribution).length === 0) {
                    distribution = buildDistribution(clusterPoints, 'source');
                }
                return L.divIcon({
                    html: createPieChartSvg(distribution, cluster.getChildCount()),
                    className: 'pie-chart-cluster-icon',
                    iconSize: [42, 42],
                    iconAnchor: [21, 21],
                });
            },
        });

        const bounds = [];

        points.forEach((point) => {
            if (!isFinite(point.latitude) || !isFinite(point.longitude)) {
                return;
            }

            const latLng = [point.latitude, point.longitude];
            bounds.push(latLng);
            const category = bucketKeyForPoint(point, currentMapColorVariable);
            const color = valueColor(currentMapColorVariable, category);

            const marker = L.marker(latLng, {
                icon: L.divIcon({ html: '', className: 'invisible-marker', iconSize: [1, 1] }),
            });

            marker.sampleData = point;
            marker.bindTooltip(`
                <strong>Record code:</strong> ${point.code || 'Unknown'}<br>
                <strong>Source code:</strong> ${point.source_code || 'Unknown'}<br>
                <strong>Source:</strong> ${point.source_type || 'Unknown'}<br>
                <strong>MPS type:</strong> ${point.mps_type || 'Unknown'}<br>
                <strong>Protocol:</strong> ${point.protocol || 'Unknown'}<br>
                <strong>Sampling site:</strong> ${point.sampling_site_name || 'Unknown'}
            `, { sticky: true });

            clusters.addLayer(marker);

            L.circle(latLng, {
                color,
                fillColor: color,
                radius: 95,
                weight: 4,
                fillOpacity: 0.35,
                interactive: false,
            }).addTo(currentMap);
        });

        currentMap.addLayer(clusters);
        if (bounds.length > 0) {
            currentMap.fitBounds(bounds, { padding: [20, 20] });
        }

        refreshLegend(points, currentMapColorVariable);
    }

    async function loadAllMapPoints() {
        const baseUrl = buildUrlWithFilters(window.microplasticsMapPointsUrl);
        if (!baseUrl) {
            return;
        }

        mapLoadToken++;
        const token = mapLoadToken;
        const allPoints = [];
        let cursor = null;

        for (let i = 0; i < 100; i++) {
            if (token !== mapLoadToken) {
                return;
            }

            const url = new URL(baseUrl.toString());
            url.searchParams.set('limit', '1000');
            if (cursor) {
                url.searchParams.set('cursor', String(cursor));
            }

            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                break;
            }

            const data = await response.json();
            const points = Array.isArray(data.points) ? data.points : [];
            allPoints.push(...points);
            cursor = data.next_cursor;

            if (!cursor || points.length === 0) {
                break;
            }
        }

        if (token !== mapLoadToken) {
            return;
        }

        rawMapPoints = allPoints;
        drawMap(rawMapPoints);
    }

    function refreshAll() {
        const pieTabs = window.microplasticsPieChartTabs || [];
        const barTabs = window.microplasticsBarChartTabs || [];
        const mapOptions = window.microplasticsMapColorVariableOptions || [];

        renderPieTabbedChart(pieTabs[0]?.key || 'source');
        renderBarTabbedChart(barTabs[0]?.key || 'laboratory');
        renderTimelineChart(window.microplasticsTimelineData || {});
        currentMapColorVariable = mapOptions[0]?.key || 'source';
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');
        loadAllMapPoints();
    }

    document.addEventListener('click', (event) => {
        const pieTab = event.target.closest('[data-pie-tab]');
        if (pieTab) {
            event.preventDefault();
            renderPieTabbedChart(pieTab.getAttribute('data-pie-tab') || '');
            return;
        }

        const barTab = event.target.closest('[data-bar-tab]');
        if (barTab) {
            event.preventDefault();
            renderBarTabbedChart(barTab.getAttribute('data-bar-tab') || '');
            return;
        }

        const mapTab = event.target.closest('[data-map-tab]');
        if (mapTab) {
            event.preventDefault();
            currentMapColorVariable = mapTab.getAttribute('data-map-tab') || 'source';
            drawMap(rawMapPoints);
        }
    });

    Livewire.on('filtersUpdated', (payload) => {
        const data = payload?.data || {};
        if (data.timelineData) {
            window.microplasticsTimelineData = data.timelineData;
        }
        if (data.pieChartTabs) {
            window.microplasticsPieChartTabs = data.pieChartTabs;
        }
        if (data.barChartTabs) {
            window.microplasticsBarChartTabs = data.barChartTabs;
        }
        if (data.mapColorVariableOptions) {
            window.microplasticsMapColorVariableOptions = data.mapColorVariableOptions;
        }
        if (data.mapPointsUrl) {
            window.microplasticsMapPointsUrl = data.mapPointsUrl;
        }
        if (data.activeFilters) {
            window.microplasticsActiveFilters = data.activeFilters;
        }
        refreshAll();
    });

    function bootstrap() {
        const hasContainers = !!(
            document.getElementById('pieTabbedChart') &&
            document.getElementById('barTabbedChart') &&
            document.getElementById('map')
        );

        if (!hasContainers || typeof Chart === 'undefined' || typeof L === 'undefined') {
            setTimeout(bootstrap, 180);
            return;
        }

        refreshAll();
    }

    document.addEventListener('DOMContentLoaded', bootstrap);
    document.addEventListener('livewire:initialized', () => setTimeout(bootstrap, 180));
    document.addEventListener('livewire:navigated', () => setTimeout(bootstrap, 180));
    window.addEventListener('load', () => setTimeout(bootstrap, 180));
})();

(function () {
    'use strict';

    if (window.__parasiteDashboardScriptLoaded) {
        return;
    }
    window.__parasiteDashboardScriptLoaded = true;

    const BASE_COLORS = [
        '#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#84cc16', '#f97316',
    ];
    const EXPORT_FORMATS = ['png', 'jpeg', 'jpg', 'tiff', 'pdf'];

    const ORIGIN_COLORS = {
        'Human samples': '#b91c1c',
        'Animal samples': '#16a34a',
        'Environmental samples': '#7c3aed',
    };

    let currentMap = null;
    let mapLoadToken = 0;
    let rawMapPoints = [];
    let currentMapColorVariable = 'origin';
    let pieChart = null;
    let barChart = null;
    let timelineChart = null;
    let bootstrapAttempts = 0;

    function normalizeValue(value) {
        const str = String(value ?? '').trim();
        return str === '' ? 'Unknown' : str;
    }

    function valueColor(variable, value) {
        if (variable === 'origin') {
            const normalizedOrigin = ({
                Human: 'Human samples',
                Animal: 'Animal samples',
                Environment: 'Environmental samples',
                HumanSamples: 'Human samples',
                AnimalSamples: 'Animal samples',
                EnvironmentSamples: 'Environmental samples',
            })[value] || value;
            return ORIGIN_COLORS[normalizedOrigin] || '#64748b';
        }
        const key = `${variable}:${value}`;
        let hash = 0;
        for (let i = 0; i < key.length; i++) {
            hash = ((hash << 5) - hash) + key.charCodeAt(i);
            hash |= 0;
        }
        const idx = Math.abs(hash) % BASE_COLORS.length;
        return BASE_COLORS[idx];
    }

    function bucketKeyForSample(sample, variable) {
        switch (variable) {
            case 'origin':
                return normalizeValue(sample.type);
            case 'parasite_species':
                return normalizeValue(sample.parasite_species);
            case 'parasite_genus':
                return normalizeValue(sample.parasite_genus);
            case 'sample_type':
                return normalizeValue(sample.sample_type);
            case 'stage':
                return normalizeValue(sample.stage);
            case 'sex':
                return normalizeValue(sample.sex);
            case 'human_ethnicity':
                return normalizeValue(sample.human_ethnicity);
            case 'human_occupation':
                return normalizeValue(sample.human_occupation);
            case 'human_country':
                return normalizeValue(sample.human_country);
            case 'animal_species':
                return normalizeValue(sample.animal_species);
            case 'animal_age':
                return normalizeValue(sample.animal_age);
            case 'animal_sex':
                return normalizeValue(sample.animal_sex);
            default:
                return normalizeValue(sample.type);
        }
    }

    function buildDistribution(samples, variable) {
        const counts = {};
        (samples || []).forEach((sample) => {
            const key = bucketKeyForSample(sample, variable);
            counts[key] = (counts[key] || 0) + 1;
        });
        return counts;
    }

    function createPieChartSvg(distribution, totalCount) {
        const size = 42;
        const radius = 17;
        const cx = 21;
        const cy = 21;

        const slices = Object.entries(distribution)
            .filter(([, count]) => count > 0)
            .map(([label, count]) => ({
                label,
                count,
                color: valueColor(currentMapColorVariable, label),
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

    function refreshLegend(points, variable) {
        const legend = document.getElementById('mapLegend');
        if (!legend) return;

        const distribution = buildDistribution(points, variable);
        const entries = Object.entries(distribution)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10);

        if (entries.length === 0) {
            legend.innerHTML = '<span class="text-gray-500">No data available for legend.</span>';
            return;
        }

        legend.innerHTML = entries.map(([label, count]) => `
            <div class="inline-flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" style="background:${valueColor(variable, label)}"></span>
                <span>${label}</span>
                <span class="text-gray-400">(${count})</span>
            </div>
        `).join('');
    }

    function createClusterTooltip(samples, variable) {
        const distribution = buildDistribution(samples, variable);
        const entries = Object.entries(distribution).sort((a, b) => b[1] - a[1]);
        const rows = entries.map(([label, count]) => {
            const pct = ((count / samples.length) * 100).toFixed(1);
            return `<div><span style="color:${valueColor(variable, label)}">●</span> ${label}: ${count} (${pct}%)</div>`;
        }).join('');

        return `
            <div style="font-size:12px;line-height:1.35;">
                <div><strong>Total samples:</strong> ${samples.length}</div>
                <div><strong>Colored by:</strong> ${variable.replace('_', ' ')}</div>
                <div style="margin-top:6px">${rows}</div>
            </div>
        `;
    }

    function drawMap(points) {
        const mapContainer = document.getElementById('map');
        if (!mapContainer || typeof L === 'undefined') return;
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');

        if (currentMap) {
            try { currentMap.remove(); } catch (_) {}
            currentMap = null;
        }
        mapContainer.innerHTML = '';
        mapContainer._leaflet_id = null;

        const map = L.map('map').setView([-28.5595, 22.9375], 5);
        currentMap = map;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
            crossOrigin: true,
        }).addTo(map);

        const clusters = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            iconCreateFunction: (cluster) => {
                const markers = cluster.getAllChildMarkers();
                const clusterSamples = markers.map((m) => m.sampleData).filter(Boolean);
                const distribution = buildDistribution(clusterSamples, currentMapColorVariable);
                const html = createPieChartSvg(distribution, cluster.getChildCount());

                return L.divIcon({
                    html,
                    className: 'pie-chart-cluster-icon',
                    iconSize: [42, 42],
                    iconAnchor: [21, 21],
                });
            },
        });

        points.forEach((sample) => {
            if (!isFinite(sample.latitude) || !isFinite(sample.longitude)) return;

            const latLng = [sample.latitude, sample.longitude];
            const category = bucketKeyForSample(sample, currentMapColorVariable);
            const color = valueColor(currentMapColorVariable, category);

            const marker = L.marker(latLng, {
                icon: L.divIcon({ html: '', className: 'invisible-marker', iconSize: [1, 1] }),
            });
            marker.sampleData = sample;
            marker.bindTooltip(`
                <strong>Sample code:</strong> ${sample.code || 'Unknown'}<br>
                <strong>Origin:</strong> ${sample.type || 'Unknown'}<br>
                <strong>Species:</strong> ${sample.parasite_species || 'Unknown'}<br>
                <strong>Category:</strong> ${category}
            `, { sticky: true });
            clusters.addLayer(marker);

            L.circle(latLng, {
                color,
                fillColor: color,
                radius: 95,
                weight: 4,
                fillOpacity: 0.35,
                interactive: false,
            }).addTo(map);
        });

        clusters.on('clustermouseover', (event) => {
            const markers = event.layer.getAllChildMarkers();
            const samples = markers.map((m) => m.sampleData).filter(Boolean);
            event.layer.bindTooltip(createClusterTooltip(samples, currentMapColorVariable), {
                sticky: true,
                direction: 'top',
            }).openTooltip();
        });
        clusters.on('clustermouseout', (event) => {
            event.layer.closeTooltip();
        });

        map.addLayer(clusters);
        refreshLegend(points, currentMapColorVariable);
    }

    async function loadAllMapPoints(url, filters) {
        mapLoadToken++;
        const token = mapLoadToken;
        const all = [];
        let cursor = 0;

        try {
            while (true) {
                if (token !== mapLoadToken) return;
                const params = new URLSearchParams();
                params.set('cursor', String(cursor));
                params.set('limit', '1500');
                Object.entries(filters || {}).forEach(([k, v]) => {
                    if (v === null || typeof v === 'undefined' || v === '') return;
                    params.set(k, String(v));
                });

                const response = await fetch(`${url}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) break;
                const payload = await response.json();
                const points = payload.points || [];
                all.push(...points);
                if (!payload.next_cursor) break;
                cursor = payload.next_cursor;
                await new Promise((resolve) => setTimeout(resolve, 0));
            }
        } catch (_) {
            // ignore network errors in UI refresh
        }

        if (token !== mapLoadToken) return;
        rawMapPoints = all;
        drawMap(rawMapPoints);
    }

    function tabDataByKey(tabs, key) {
        const selected = (tabs || []).find((tab) => tab.key === key) || tabs?.[0];
        return selected || { key: '', label: '', data: {} };
    }

    function buildPieFallbackTab(key) {
        const fallbackMap = {
            origin: window.parasiteSamplesByOrigin || {},
            stage: window.parasiteSamplesByStage || {},
            sex: window.parasiteSamplesBySex || {},
            sample_type: window.parasiteSamplesBySampleType || {},
        };
        return fallbackMap[key] || {};
    }

    function buildBarFallbackTab(key) {
        const fallbackMap = {
            species: window.parasiteSamplesBySpecies || {},
            genus: window.parasiteSamplesByGenus || {},
        };
        return fallbackMap[key] || {};
    }

    function ensureDefaultTabPayloads() {
        if (!Array.isArray(window.pieChartTabs) || window.pieChartTabs.length === 0) {
            window.pieChartTabs = [
                { key: 'origin', label: 'Origin', data: window.parasiteSamplesByOrigin || {} },
                { key: 'stage', label: 'Parasite Stage', data: window.parasiteSamplesByStage || {} },
                { key: 'sex', label: 'Parasite Sex', data: window.parasiteSamplesBySex || {} },
                { key: 'sample_type', label: 'Sample Type', data: window.parasiteSamplesBySampleType || {} },
            ];
        }

        if (!Array.isArray(window.barChartTabs) || window.barChartTabs.length === 0) {
            window.barChartTabs = [
                { key: 'species', label: 'Parasite Species', data: window.parasiteSamplesBySpecies || {} },
                { key: 'genus', label: 'Parasite Genus', data: window.parasiteSamplesByGenus || {} },
            ];
        }

        if (!Array.isArray(window.mapColorVariableOptions) || window.mapColorVariableOptions.length === 0) {
            window.mapColorVariableOptions = [
                { key: 'origin', label: 'Origin type' },
                { key: 'parasite_species', label: 'Parasite species' },
                { key: 'parasite_genus', label: 'Parasite genus' },
                { key: 'sample_type', label: 'Parasite sample type' },
                { key: 'stage', label: 'Parasite stage' },
                { key: 'sex', label: 'Parasite sex' },
            ];
        }
    }

    function setActiveTabButtons(selector, activeKey, dataAttribute) {
        document.querySelectorAll(selector).forEach((btn) => {
            if (btn.getAttribute(dataAttribute) === activeKey) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        });
    }

    function renderPieTabbedChart(tabKey) {
        const tabs = window.pieChartTabs || [];
        const selected = tabDataByKey(tabs, tabKey);
        const ctx = document.getElementById('pieTabbedChart');
        if (!ctx || typeof Chart === 'undefined') return;

        const labels = Object.keys(selected.data || {});
        const values = Object.values(selected.data || {});
        const effectiveData = labels.length === 0 ? buildPieFallbackTab(selected.key) : (selected.data || {});
        const effectiveLabels = Object.keys(effectiveData);
        const effectiveValues = Object.values(effectiveData);
        const colors = effectiveLabels.map((label) => valueColor(selected.key, label));

        if (pieChart) {
            pieChart.destroy();
        }

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: effectiveLabels,
                datasets: [{ data: effectiveValues, backgroundColor: colors, borderWidth: 1 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
            },
        });
        renderPieLegend(effectiveLabels, colors, effectiveValues);
        setActiveTabButtons('[data-pie-tab]', selected.key, 'data-pie-tab');
    }
    window.renderPieTabbedChart = renderPieTabbedChart;

    function renderBarTabbedChart(tabKey) {
        const tabs = window.barChartTabs || [];
        const selected = tabDataByKey(tabs, tabKey);
        const ctx = document.getElementById('barTabbedChart');
        if (!ctx || typeof Chart === 'undefined') return;

        const entries = Object.entries(selected.data || {}).sort((a, b) => b[1] - a[1]).slice(0, 20);
        const sourceData = entries.length === 0
            ? Object.entries(buildBarFallbackTab(selected.key))
            : entries;
        const labels = sourceData.map(([label]) => label);
        const values = sourceData.map(([, value]) => value);
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (barChart) {
            barChart.destroy();
        }

        barChart = new Chart(ctx, {
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
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { maxRotation: 45, minRotation: 10 } },
                },
            },
        });
        setActiveTabButtons('[data-bar-tab]', selected.key, 'data-bar-tab');
    }
    window.renderBarTabbedChart = renderBarTabbedChart;

    function renderTimelineChart(data) {
        const ctx = document.getElementById('timelineChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = Object.keys(data || {});
        const values = Object.values(data || {});

        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Samples Collected',
                    data: values,
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

    function initMapColorControl() {
        const firstMapTab = document.querySelector('[data-map-tab]');
        currentMapColorVariable = firstMapTab?.getAttribute('data-map-tab') || 'origin';
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');
    }

    function renderPieLegend(labels, colors, values) {
        const container = document.getElementById('pieLegendScroller');
        if (!container) return;
        if (!labels || labels.length === 0) {
            container.innerHTML = '';
            return;
        }
        container.innerHTML = `
            <div class="inline-flex w-max min-w-full items-center gap-4 pb-1 pr-3">
                ${labels.map((label, idx) => `
                    <div class="inline-flex items-center gap-2 text-xs text-gray-700">
                        <span class="w-3 h-3 rounded-full" style="background:${colors[idx]}"></span>
                        <span class="whitespace-nowrap">${label} (${values[idx] ?? 0})</span>
                    </div>
                `).join('')}
            </div>
        `;
        container.scrollLeft = 0;
    }

    function resizeVisualsFor(targetId) {
        if (targetId === 'pieBox' && pieChart) {
            setTimeout(() => {
                pieChart.resize();
                pieChart.update('none');
            }, 120);
            return;
        }

        if (targetId === 'barBox' && barChart) {
            setTimeout(() => {
                barChart.resize();
                barChart.update('none');
            }, 120);
            return;
        }

        if (targetId === 'mapBox' && currentMap) {
            setTimeout(() => currentMap.invalidateSize(), 160);
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
            const x = (pageW - w) / 2;
            const y = (pageH - h) / 2;
            pdf.addImage(dataUrl, 'PNG', x, y, w, h);
            pdf.save(`${fileName}.pdf`);
        };
        img.src = dataUrl;
    }

    function exportCanvas(canvas, format, fileName) {
        if (!canvas) return;
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

    async function exportElement(element, format, fileName) {
        if (!element || typeof html2canvas === 'undefined') return;
        const canvas = await html2canvas(element, {
            useCORS: true,
            backgroundColor: '#ffffff',
            scale: 2,
            ignoreElements: (node) => {
                return !!node.closest?.('.dashboard-box-tools');
            },
        });
        exportCanvas(canvas, format, fileName);
    }

    function currentFormatFor(target) {
        const btn = document.querySelector(`[data-format-target="${target}"]`);
        const value = btn?.getAttribute('data-format') || 'png';
        return EXPORT_FORMATS.includes(value) ? value : 'png';
    }

    function cycleFormatFor(target) {
        const btn = document.querySelector(`[data-format-target="${target}"]`);
        if (!btn) return;
        const current = currentFormatFor(target);
        const idx = EXPORT_FORMATS.indexOf(current);
        const next = EXPORT_FORMATS[(idx + 1) % EXPORT_FORMATS.length];
        btn.setAttribute('data-format', next);
        btn.setAttribute('title', `Format: ${next} (click to change)`);
        btn.setAttribute('aria-label', `Format ${next}`);
    }

    function initializeWidgets() {
        ensureDefaultTabPayloads();
        initMapColorControl();
        const pieTabs = window.pieChartTabs || [];
        const barTabs = window.barChartTabs || [];
        renderPieTabbedChart(pieTabs[0]?.key || 'origin');
        renderBarTabbedChart(barTabs[0]?.key || 'species');
        renderTimelineChart(window.timelineData || {});

        if (window.mapPointsUrl) {
            loadAllMapPoints(window.mapPointsUrl, window.activeFilters || {});
        } else {
            rawMapPoints = [];
            drawMap(rawMapPoints);
        }
    }

    function bootstrapWidgets() {
        const hasContainers = !!(
            document.getElementById('pieTabbedChart') &&
            document.getElementById('barTabbedChart') &&
            document.getElementById('map')
        );
        const hasChart = typeof Chart !== 'undefined';
        const hasLeaflet = typeof L !== 'undefined';

        if (hasContainers && hasChart && hasLeaflet) {
            initializeWidgets();
            return;
        }

        if (bootstrapAttempts >= 30) {
            return;
        }

        bootstrapAttempts += 1;
        setTimeout(bootstrapWidgets, 150);
    }

    window.loadParasiteDashboardModal = function loadParasiteDashboardModal(modalId) {
        const urls = window.modalTableUrls || {};
        const baseUrl = urls[modalId];
        if (!baseUrl) return;
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const content = modal.querySelector('[data-modal-content]');
        if (!content) return;

        const params = new URLSearchParams();
        Object.entries(window.activeFilters || {}).forEach(([k, v]) => {
            if (v === null || typeof v === 'undefined' || v === '') return;
            params.set(k, String(v));
        });
        const url = `${baseUrl}?${params.toString()}`;
        content.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => (r.ok ? r.text() : Promise.reject()))
            .then((html) => {
                content.innerHTML = html;
                window.enhanceDashboardModalTable?.(content);
            })
            .catch(() => { content.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>'; });
    };

    document.addEventListener('click', function (e) {
        const pieTab = e.target.closest('[data-pie-tab]');
        if (pieTab) {
            e.preventDefault();
            renderPieTabbedChart(pieTab.getAttribute('data-pie-tab') || '');
            return;
        }

        const barTab = e.target.closest('[data-bar-tab]');
        if (barTab) {
            e.preventDefault();
            renderBarTabbedChart(barTab.getAttribute('data-bar-tab') || '');
            return;
        }

        const mapTab = e.target.closest('[data-map-tab]');
        if (mapTab) {
            e.preventDefault();
            currentMapColorVariable = mapTab.getAttribute('data-map-tab') || 'origin';
            setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');
            drawMap(rawMapPoints);
            return;
        }

        const expandBtn = e.target.closest('[data-expand-target]');
        if (expandBtn) {
            e.preventDefault();
            const targetId = expandBtn.getAttribute('data-expand-target');
            const target = document.getElementById(targetId);
            if (!target) return;
            target.classList.toggle('is-expanded');
            document.body.classList.toggle('dashboard-box-open', !!document.querySelector('.dashboard-box.is-expanded'));
            resizeVisualsFor(targetId);
            return;
        }

        const downloadBtn = e.target.closest('[data-download-target]');
        if (downloadBtn) {
            e.preventDefault();
            const target = downloadBtn.getAttribute('data-download-target');
            if (target === 'pie') {
                const format = currentFormatFor('pie');
                exportElement(document.getElementById('pieBox'), format, 'parasite-pie-chart');
            } else if (target === 'bar') {
                const format = currentFormatFor('bar');
                exportElement(document.getElementById('barBox'), format, 'parasite-bar-chart');
            } else if (target === 'map') {
                const format = currentFormatFor('map');
                exportElement(document.getElementById('mapBox'), format, 'parasite-map');
            }
            return;
        }

        const formatBtn = e.target.closest('[data-format-target]');
        if (formatBtn) {
            e.preventDefault();
            const target = formatBtn.getAttribute('data-format-target');
            cycleFormatFor(target);
            return;
        }

        const anchor = e.target.closest('a[href]');
        if (!anchor) return;
        const modal = anchor.closest('#samplesModal, #humanSamplesModal, #animalSamplesModal, #environmentSamplesModal');
        if (!modal) return;
        const nav = anchor.closest('nav');
        if (!nav) return;

        e.preventDefault();
        const content = modal.querySelector('[data-modal-content]');
        if (!content) return;
        content.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
        fetch(anchor.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => (r.ok ? r.text() : Promise.reject()))
            .then((html) => {
                content.innerHTML = html;
                window.enhanceDashboardModalTable?.(content);
            })
            .catch(() => { content.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>'; });
    });

    Livewire.on('filtersUpdated', (payload) => {
        const data = payload?.data || {};
        if (data.mapPointsUrl) window.mapPointsUrl = data.mapPointsUrl;
        if (data.activeFilters) window.activeFilters = data.activeFilters;
        if (data.pieChartTabs) window.pieChartTabs = data.pieChartTabs;
        if (data.barChartTabs) window.barChartTabs = data.barChartTabs;
        if (data.parasiteSamplesByOrigin) window.parasiteSamplesByOrigin = data.parasiteSamplesByOrigin;
        if (data.parasiteSamplesByStage) window.parasiteSamplesByStage = data.parasiteSamplesByStage;
        if (data.parasiteSamplesBySex) window.parasiteSamplesBySex = data.parasiteSamplesBySex;
        if (data.parasiteSamplesBySpecies) window.parasiteSamplesBySpecies = data.parasiteSamplesBySpecies;
        if (data.parasiteSamplesByGenus) window.parasiteSamplesByGenus = data.parasiteSamplesByGenus;
        if (data.parasiteSamplesBySampleType) window.parasiteSamplesBySampleType = data.parasiteSamplesBySampleType;
        if (data.descriptive_stats?.collection_timeline) window.timelineData = data.descriptive_stats.collection_timeline;

        const pieTabs = window.pieChartTabs || [];
        const barTabs = window.barChartTabs || [];
        renderPieTabbedChart(pieTabs[0]?.key || 'origin');
        renderBarTabbedChart(barTabs[0]?.key || 'species');
        renderTimelineChart(window.timelineData || {});

        loadAllMapPoints(window.mapPointsUrl, window.activeFilters || {});
    });

    document.addEventListener('DOMContentLoaded', bootstrapWidgets);
    document.addEventListener('livewire:initialized', () => setTimeout(bootstrapWidgets, 200));
    document.addEventListener('livewire:navigated', () => setTimeout(bootstrapWidgets, 200));
    window.addEventListener('load', () => setTimeout(bootstrapWidgets, 200));
    setTimeout(bootstrapWidgets, 0);
})();
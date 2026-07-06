(function () {
    'use strict';

    if (window.__nucleicDashboardScriptLoaded) {
        return;
    }
    window.__nucleicDashboardScriptLoaded = true;

    const BASE_COLORS = [
        '#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#84cc16', '#f97316',
    ];
    const EXPORT_FORMATS = ['png', 'jpeg', 'jpg', 'tiff', 'pdf'];

    const SOURCE_COLORS = {
        Human: '#b91c1c',
        HumanSamples: '#b91c1c',
        Animal: '#16a34a',
        AnimalSamples: '#16a34a',
        Environment: '#7c3aed',
        EnvironmentSamples: '#7c3aed',
        Parasite: '#ea580c',
        ParasiteSamples: '#ea580c',
        Culture: '#ca8a04',
        Cultures: '#ca8a04',
        Pool: '#4f46e5',
        Pools: '#4f46e5',
    };

    let currentMap = null;
    let mapLoadToken = 0;
    let rawMapPoints = [];
    let currentMapColorVariable = 'source';
    let pieChart = null;
    let barChart = null;
    let timelineChart = null;

    function normalizeValue(value) {
        const str = String(value ?? '').trim();
        return str === '' ? 'Unknown' : str;
    }

    function acronymizeLabel(label) {
        const value = String(label ?? '').trim();
        if (value === '') {
            return 'N/A';
        }

        const words = value
            .replace(/[^\w\s-]/g, ' ')
            .split(/[\s_-]+/)
            .filter(Boolean);

        if (words.length <= 1) {
            return value.length > 12 ? `${value.slice(0, 10)}..` : value;
        }

        const acronym = words.map((word) => word[0].toUpperCase()).join('');
        return acronym.length >= 2 ? acronym : value;
    }

    function valueColor(variable, value) {
        if (variable === 'source') {
            const normalized = normalizeValue(value);
            return SOURCE_COLORS[normalized] || '#64748b';
        }
        const key = `${variable}:${value}`;
        let hash = 0;
        for (let i = 0; i < key.length; i++) {
            hash = ((hash << 5) - hash) + key.charCodeAt(i);
            hash |= 0;
        }
        return BASE_COLORS[Math.abs(hash) % BASE_COLORS.length];
    }

    function bucketKeyForSample(sample, variable) {
        switch (variable) {
            case 'source':
                return normalizeValue(sample.source_type);
            case 'type':
                return normalizeValue(sample.type);
            case 'protocol':
                return normalizeValue(sample.protocol);
            case 'extracted_by':
                return normalizeValue(sample.extracted_by);
            case 'human_ethnicity':
                return normalizeValue(sample.human_ethnicity);
            case 'human_occupation':
                return normalizeValue(sample.human_occupation);
            case 'human_country':
                return normalizeValue(sample.human_country);
            case 'animal_species':
                return normalizeValue(sample.animal_species);
            case 'animal_sex':
                return normalizeValue(sample.animal_sex);
            case 'animal_age':
                return normalizeValue(sample.animal_age);
            case 'parasite_species':
                return normalizeValue(sample.parasite_species);
            case 'parasite_stage':
                return normalizeValue(sample.parasite_stage);
            case 'parasite_sex':
                return normalizeValue(sample.parasite_sex);
            case 'culture_type':
                return normalizeValue(sample.culture_type);
            case 'culture_medium':
                return normalizeValue(sample.culture_medium);
            case 'pool_nr_pooled':
                return normalizeValue(sample.pool_nr_pooled);
            default:
                return normalizeValue(sample.source_type);
        }
    }

    function buildDistribution(samples, variable) {
        const counts = {};
        (samples || []).forEach((sample) => {
            const key = bucketKeyForSample(sample, variable);
            if (key === 'Unknown') {
                return;
            }
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
                <circle cx="${cx}" cy="${cy}" r="${radius}" fill="${slices[0].color}" stroke="${slices[0].color}" stroke-width="1"/>
                <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
            </svg>`;
        }

        const startAngle = -Math.PI / 2;
        let currentAngle = startAngle;
        const total = slices.reduce((sum, item) => sum + item.count, 0);
        let paths = '';

        slices.forEach((item, index) => {
            const angle = (item.count / total) * 2 * Math.PI;
            const endAngle = index === slices.length - 1 ? (startAngle + (2 * Math.PI)) : (currentAngle + angle);
            const x1 = cx + radius * Math.cos(currentAngle);
            const y1 = cy + radius * Math.sin(currentAngle);
            const x2 = cx + radius * Math.cos(endAngle);
            const y2 = cy + radius * Math.sin(endAngle);
            const largeArc = angle > Math.PI ? 1 : 0;
            const d = `M ${cx} ${cy} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2} Z`;
            paths += `<path d="${d}" fill="${item.color}" stroke="${item.color}" stroke-width="0.8"/>`;
            currentAngle = endAngle;
        });

        return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
            ${paths}
            <text x="${cx}" y="${cy + 4}" text-anchor="middle" font-size="11" font-weight="800" fill="#ffffff" stroke="rgba(15,23,42,0.45)" stroke-width="0.8" paint-order="stroke">${totalCount}</text>
        </svg>`;
    }

    function refreshLegend(points, variable) {
        const legend = document.getElementById('mapLegend');
        if (!legend) return;
        const distribution = buildDistribution(points, variable);
        const entries = Object.entries(distribution).sort((a, b) => b[1] - a[1]).slice(0, 12);

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

    function setActiveTabButtons(selector, activeKey, dataAttr) {
        document.querySelectorAll(selector).forEach((btn) => {
            if (btn.getAttribute(dataAttr) === activeKey) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        });
    }

    function tabDataByKey(tabs, key) {
        const selected = (tabs || []).find((tab) => tab.key === key) || tabs?.[0];
        return selected || { key: '', label: '', data: {} };
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
    }

    function renderPieTabbedChart(tabKey) {
        const selected = tabDataByKey(window.pieChartTabs || [], tabKey);
        const ctx = document.getElementById('pieTabbedChart');
        if (!ctx || typeof Chart === 'undefined') return;

        const labels = Object.keys(selected.data || {});
        const values = Object.values(selected.data || {});
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (pieChart) {
            pieChart.destroy();
        }

        pieChart = new Chart(ctx, {
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
        const selected = tabDataByKey(window.barChartTabs || [], tabKey);
        const ctx = document.getElementById('barTabbedChart');
        if (!ctx || typeof Chart === 'undefined') return;

        const entries = Object.entries(selected.data || {}).sort((a, b) => b[1] - a[1]).slice(0, 20);
        const labels = entries.map(([label]) => label);
        const values = entries.map(([, value]) => value);
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
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 10,
                            callback: function (value, index) {
                                const label = labels[index] ?? '';
                                if (selected.key === 'protocol' || selected.key === 'laboratory') {
                                    return acronymizeLabel(label);
                                }
                                return label;
                            },
                        },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title(items) {
                                const item = items?.[0];
                                if (!item) return '';
                                const original = labels[item.dataIndex] ?? '';
                                if (selected.key === 'protocol' || selected.key === 'laboratory') {
                                    return original;
                                }
                                return item.label;
                            },
                        },
                    },
                },
            },
        });

        setActiveTabButtons('[data-bar-tab]', selected.key, 'data-bar-tab');
    }

    function renderTimelineChart(data) {
        const ctx = document.getElementById('timelineChart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(data || {}),
                datasets: [{
                    label: 'Samples Extracted',
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
                let distribution = buildDistribution(clusterSamples, currentMapColorVariable);
                if (Object.keys(distribution).length === 0) {
                    // Fallback to source colors so markers never render as blank/white.
                    distribution = buildDistribution(clusterSamples, 'source');
                }
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
                <strong>Source:</strong> ${sample.source_type || 'Unknown'}<br>
                <strong>Type:</strong> ${sample.type || 'Unknown'}<br>
                <strong>Protocol:</strong> ${sample.protocol || 'Unknown'}
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

        map.addLayer(clusters);
        refreshLegend(points, currentMapColorVariable);
    }

    function buildUrlWithFilters(base) {
        if (!base) return null;
        const url = new URL(base, window.location.origin);
        const filters = window.activeFilters || {};
        Object.entries(filters).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') return;
            url.searchParams.set(key, String(value));
        });
        return url;
    }

    async function loadAllMapPoints() {
        const baseUrl = buildUrlWithFilters(window.mapPointsUrl);
        if (!baseUrl) return;

        mapLoadToken++;
        const token = mapLoadToken;
        const all = [];
        let cursor = null;

        for (let i = 0; i < 100; i++) {
            if (token !== mapLoadToken) return;
            const url = new URL(baseUrl.toString());
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

        if (token !== mapLoadToken) return;
        rawMapPoints = all;
        drawMap(rawMapPoints);
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

    function exportCanvas(canvas, format, fileName) {
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
            ignoreElements: (node) => !!node.closest?.('.dashboard-box-tools'),
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

    function refreshAll() {
        const pieTabs = window.pieChartTabs || [];
        const barTabs = window.barChartTabs || [];
        const mapOptions = window.mapColorVariableOptions || [];

        renderPieTabbedChart(pieTabs[0]?.key || 'source');
        renderBarTabbedChart(barTabs[0]?.key || 'protocol');
        renderTimelineChart(window.timelineData || {});
        currentMapColorVariable = mapOptions[0]?.key || 'source';
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');
        loadAllMapPoints();
    }

    window.loadNucleicDashboardModal = function loadNucleicDashboardModal(modalId) {
        const base = window.modalTableUrls?.[modalId];
        if (!base) return;

        const content = document.getElementById(`${modalId}Content`);
        if (!content) return;

        const url = buildUrlWithFilters(base);
        if (!url) return;

        content.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
            currentMapColorVariable = mapTab.getAttribute('data-map-tab') || 'source';
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

        const formatBtn = e.target.closest('[data-format-target]');
        if (formatBtn) {
            e.preventDefault();
            cycleFormatFor(formatBtn.getAttribute('data-format-target'));
            return;
        }

        const downloadBtn = e.target.closest('[data-download-target]');
        if (downloadBtn) {
            e.preventDefault();
            const target = downloadBtn.getAttribute('data-download-target');
            if (target === 'pie') {
                exportElement(document.getElementById('pieBox'), currentFormatFor('pie'), 'nucleic-pie-chart');
            } else if (target === 'bar') {
                exportElement(document.getElementById('barBox'), currentFormatFor('bar'), 'nucleic-bar-chart');
            } else if (target === 'map') {
                exportElement(document.getElementById('mapBox'), currentFormatFor('map'), 'nucleic-map');
            }
            return;
        }

        const anchor = e.target.closest('[data-modal-content] nav a[href]');
        if (!anchor) return;
        e.preventDefault();
        const container = anchor.closest('[data-modal-content]');
        if (!container) return;
        const url = buildUrlWithFilters(anchor.getAttribute('href'));
        if (!url) return;
        container.innerHTML = '<div class="text-sm text-gray-500">Loading…</div>';
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => (r.ok ? r.text() : Promise.reject()))
            .then((html) => {
                container.innerHTML = html;
                window.enhanceDashboardModalTable?.(container);
            })
            .catch(() => { container.innerHTML = '<div class="text-sm text-red-600">Failed to load data.</div>'; });
    });

    Livewire.on('filtersUpdated', (payload) => {
        const data = payload?.data || {};
        if (data.descriptive_stats?.extraction_timeline) window.timelineData = data.descriptive_stats.extraction_timeline;
        if (data.nucleicAcidsBySource) window.nucleicAcidsBySource = data.nucleicAcidsBySource;
        if (data.nucleicAcidsByType) window.nucleicAcidsByType = data.nucleicAcidsByType;
        if (data.nucleicAcidsByProtocol) window.nucleicAcidsByProtocol = data.nucleicAcidsByProtocol;
        if (data.nucleicAcidsByLaboratory) window.nucleicAcidsByLaboratory = data.nucleicAcidsByLaboratory;
        if (data.nucleicAcidsByExtractedBy) window.nucleicAcidsByExtractedBy = data.nucleicAcidsByExtractedBy;
        if (data.pieChartTabs) window.pieChartTabs = data.pieChartTabs;
        if (data.barChartTabs) window.barChartTabs = data.barChartTabs;
        if (data.mapColorVariableOptions) window.mapColorVariableOptions = data.mapColorVariableOptions;
        if (data.mapPointsUrl) window.mapPointsUrl = data.mapPointsUrl;
        if (data.activeFilters) window.activeFilters = data.activeFilters;
        if (data.modalTableUrls) window.modalTableUrls = data.modalTableUrls;
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
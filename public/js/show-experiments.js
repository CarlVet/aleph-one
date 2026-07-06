(function () {
    'use strict';

    if (window.__experimentsDashboardScriptLoaded) {
        return;
    }
    window.__experimentsDashboardScriptLoaded = true;

    const BASE_COLORS = ['#4f46e5', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#84cc16', '#f97316'];
    const EXPORT_FORMATS = ['png', 'jpeg', 'jpg', 'tiff', 'pdf'];
    const OUTCOME_COLORS = {
        'Strong positive': 'rgba(185, 28, 28, 0.8)',
        Positive: 'rgba(239, 68, 68, 0.8)',
        Suspect: 'rgba(234, 179, 8, 0.8)',
        Negative: 'rgba(34, 197, 94, 0.8)',
        Unknown: '#64748b',
    };

    let currentMap = null;
    let currentClusters = null;
    let mapLoadToken = 0;
    let currentMapColorVariable = 'outcome';
    let pieChart = null;
    let barChart = null;
    let timelineChart = null;
    let samplingSitesSummary = new Map();
    let aggregatedMapPoints = [];
    let currentCoordinateCircles = null;
    let refreshTimer = null;
    let expandedContentState = null;
    let filterRefreshTimer = null;
    const MAP_PAYLOAD_CACHE_PREFIX = 'experiments-dashboard-map-v3:';
    const MAP_CHUNK_SIZE = 500;
    let mapLoadingHideTimer = null;

    function filterRoot() {
        return document.getElementById('experiments-dashboard-filters');
    }

    function getDashboardComponent() {
        const root = filterRoot();
        const componentEl = root ? root.closest('[wire\\:id]') : null;
        const componentId = componentEl ? componentEl.getAttribute('wire:id') : null;
        if (!componentId || !window.Livewire?.find) {
            return null;
        }

        return window.Livewire.find(componentId);
    }

    function resolveDashboardFilterDisplay(select) {
        const currentValue = String(select.getAttribute('data-current-value') ?? '').trim();
        if (currentValue === '' || currentValue === 'all') {
            return '';
        }

        const match = [...select.options].find((option) => String(option.value).trim() === currentValue);

        return match ? String(match.textContent || '').trim() : currentValue;
    }

    function resolveDashboardFilterValue(select, displayValue) {
        const normalizedDisplayValue = String(displayValue ?? '').trim();
        if (normalizedDisplayValue === '') {
            return '';
        }

        const options = [...select.options];
        const byText = options.find((option) => String(option.textContent || '').trim().toLowerCase() === normalizedDisplayValue.toLowerCase());
        if (byText) {
            const optionValue = String(byText.value ?? '').trim();

            return optionValue === '' || optionValue === 'all' ? '' : optionValue;
        }

        const byValue = options.find((option) => String(option.value || '').trim().toLowerCase() === normalizedDisplayValue.toLowerCase());
        if (byValue) {
            const optionValue = String(byValue.value ?? '').trim();

            return optionValue === '' || optionValue === 'all' ? '' : optionValue;
        }

        return null;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function experimentProfileUrl(code) {
        return `/experiments/${encodeURIComponent(String(code || '').trim())}`;
    }

    function buildDashboardFilterDatalist(select) {
        const propName = select.getAttribute('data-dashboard-filter-model') ?? '';
        if (propName === '') {
            return;
        }

        select.classList.add('hidden');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;

        const shellSelector = `[data-dashboard-datalist-shell="${propName}"]`;
        let shell = select.parentElement?.querySelector(shellSelector);
        if (!shell) {
            shell = document.createElement('div');
            shell.setAttribute('data-dashboard-datalist-shell', propName);
            select.insertAdjacentElement('afterend', shell);
        }

        const listId = `dashboard-filter-list-${propName}`;
        const firstOption = select.querySelector('option');
        const inputClassName = select.className.replace(/\bhidden\b/g, '').trim();
        const currentDisplayValue = resolveDashboardFilterDisplay(select);
        const inputValueAttr = escapeHtml(currentDisplayValue);
        const placeholder = escapeHtml(String(firstOption?.textContent || 'Type to filter').trim());
        const combinedInputClassName = `${inputClassName} bg-white`.trim();
        const optionLabels = [...select.options]
            .filter((option) => {
                const optionValue = String(option.value ?? '').trim();

                return optionValue !== '' && optionValue !== 'all';
            })
            .map((option) => String(option.textContent || option.value || '').trim());

        shell.innerHTML = `
            <div class="relative" data-dashboard-autocomplete="${propName}">
                <input
                    type="text"
                    value="${inputValueAttr}"
                    placeholder="${placeholder}"
                    class="${combinedInputClassName}"
                    data-dashboard-datalist-input="${propName}"
                    autocomplete="off"
                    spellcheck="false"
                >
                <div
                    class="dashboard-autocomplete-panel absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-xl"
                    data-dashboard-autocomplete-panel="${propName}"
                ></div>
            </div>
        `;

        const input = shell.querySelector(`[data-dashboard-datalist-input="${propName}"]`);
        const panel = shell.querySelector(`[data-dashboard-autocomplete-panel="${propName}"]`);
        if (!input) {
            return;
        }

        let syncTimer = null;
        let activeIndex = -1;

        const filteredLabels = () => {
            const query = String(input.value || '').trim().toLowerCase();
            if (query === '') {
                return optionLabels.slice(0, 50);
            }

            return optionLabels
                .filter((label) => label.toLowerCase().includes(query))
                .slice(0, 50);
        };

        const hidePanel = () => {
            if (!panel) {
                return;
            }

            panel.classList.add('hidden');
            panel.innerHTML = '';
            activeIndex = -1;
        };

        const renderPanel = () => {
            if (!panel) {
                return;
            }

            const matches = filteredLabels();
            if (matches.length === 0) {
                hidePanel();
                return;
            }

            panel.innerHTML = matches.map((label, index) => `
                <button
                    type="button"
                    class="dashboard-autocomplete-option flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-blue-50 hover:text-blue-700 ${index === activeIndex ? 'bg-blue-50 text-blue-700' : ''}"
                    data-dashboard-option-index="${index}"
                    data-dashboard-option-label="${escapeHtml(label)}"
                >
                    ${escapeHtml(label)}
                </button>
            `).join('');
            panel.classList.remove('hidden');
        };

        const syncValue = () => {
            const component = getDashboardComponent();
            if (!component) {
                return;
            }

            const resolvedValue = resolveDashboardFilterValue(select, input.value);
            if (resolvedValue === null) {
                return;
            }

            const currentAppliedValue = String(select.getAttribute('data-current-value') ?? '').trim();
            if (currentAppliedValue === resolvedValue) {
                return;
            }

            select.setAttribute('data-current-value', resolvedValue);
            component.set(propName, resolvedValue);
        };

        input.addEventListener('input', () => {
            if (syncTimer) {
                clearTimeout(syncTimer);
            }

            if (String(input.value || '').trim() === '') {
                syncTimer = window.setTimeout(syncValue, 150);
            }

            renderPanel();
        });

        input.addEventListener('focus', renderPanel);
        input.addEventListener('keydown', (event) => {
            const matches = filteredLabels();
            if (matches.length === 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = activeIndex < matches.length - 1 ? activeIndex + 1 : 0;
                renderPanel();
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = activeIndex > 0 ? activeIndex - 1 : matches.length - 1;
                renderPanel();
                return;
            }

            if (event.key === 'Enter') {
                const targetLabel = activeIndex >= 0 ? matches[activeIndex] : input.value;
                const resolvedValue = resolveDashboardFilterValue(select, targetLabel);
                if (resolvedValue !== null) {
                    event.preventDefault();
                    input.value = targetLabel;
                    syncValue();
                    hidePanel();
                }
                return;
            }

            if (event.key === 'Escape') {
                hidePanel();
            }
        });

        panel?.addEventListener('mousedown', (event) => {
            const option = event.target.closest('[data-dashboard-option-label]');
            if (!option) {
                return;
            }

            event.preventDefault();
            input.value = option.getAttribute('data-dashboard-option-label') || '';
            syncValue();
            hidePanel();
        });

        input.addEventListener('blur', () => {
            window.setTimeout(hidePanel, 120);
        });
    }

    function initDashboardFilterDatalists() {
        const root = filterRoot();
        if (!root) {
            return;
        }

        root.querySelectorAll('select[data-dashboard-filter-model]').forEach((select) => {
            buildDashboardFilterDatalist(select);
        });
    }

    function scheduleDashboardFilterDatalists(delay = 40) {
        if (filterRefreshTimer) {
            clearTimeout(filterRefreshTimer);
        }

        filterRefreshTimer = window.setTimeout(() => {
            initDashboardFilterDatalists();
        }, delay);
    }

    function registerLivewireDashboardHooks() {
        if (window.__experimentsDashboardHooksRegistered || !window.Livewire?.hook) {
            return;
        }

        window.__experimentsDashboardHooksRegistered = true;

        Livewire.hook('request', ({ succeed }) => {
            if (typeof succeed !== 'function') {
                return;
            }

            succeed(() => {
                scheduleDashboardFilterDatalists(0);
            });
        });

        try {
            Livewire.hook('morphed', ({ el }) => {
                const root = filterRoot();
                if (!root || !el) {
                    return;
                }

                if (el === root || (typeof el.contains === 'function' && el.contains(root))) {
                    scheduleDashboardFilterDatalists(0);
                }
            });
        } catch (error) {
            // Ignore if this hook is unavailable in the shipped Livewire version.
        }
    }

    function normalizeValue(value) {
        const v = String(value ?? '').trim();
        return v === '' ? 'Unknown' : v;
    }

    function acronymizeLabel(label) {
        const value = String(label ?? '').trim();
        if (value === '') return 'N/A';
        const words = value.replace(/[^\w\s-]/g, ' ').split(/[\s_-]+/).filter(Boolean);
        if (words.length <= 1) return value.length > 12 ? `${value.slice(0, 10)}..` : value;
        const acronym = words.map((w) => w[0].toUpperCase()).join('');
        return acronym.length >= 2 ? acronym : value;
    }

    function valueColor(variable, value) {
        const normalized = normalizeValue(value);
        if (variable === 'outcome') {
            return OUTCOME_COLORS[normalized] || OUTCOME_COLORS.Unknown;
        }
        const key = `${variable}:${normalized}`;
        let hash = 0;
        for (let i = 0; i < key.length; i++) {
            hash = ((hash << 5) - hash) + key.charCodeAt(i);
            hash |= 0;
        }
        return BASE_COLORS[Math.abs(hash) % BASE_COLORS.length];
    }

    function bucketKeyForSample(sample, variable) {
        switch (variable) {
            case 'outcome': return normalizeValue(sample.outcome_discrete);
            case 'sample_type':
            case 'type': return normalizeValue(sample.type);
            case 'protocol': return normalizeValue(sample.protocol);
            case 'pathogen': return normalizeValue(sample.pathogen);
            case 'technique_type': return normalizeValue(sample.technique_type);
            case 'laboratory': return normalizeValue(sample.laboratory);
            case 'human_ethnicity': return normalizeValue(sample.human_ethnicity);
            case 'human_occupation': return normalizeValue(sample.human_occupation);
            case 'human_country': return normalizeValue(sample.human_country);
            case 'animal_species': return normalizeValue(sample.animal_species);
            case 'animal_sex': return normalizeValue(sample.animal_sex);
            case 'animal_age': return normalizeValue(sample.animal_age);
            case 'parasite_species': return normalizeValue(sample.parasite_species);
            case 'parasite_stage': return normalizeValue(sample.parasite_stage);
            case 'parasite_sex': return normalizeValue(sample.parasite_sex);
            case 'culture_type': return normalizeValue(sample.culture_type);
            case 'culture_medium': return normalizeValue(sample.culture_medium);
            case 'pool_nr_pooled': return normalizeValue(sample.pool_nr_pooled);
            case 'environment_sample_type': return normalizeValue(sample.environment_sample_type);
            case 'parasite_state': return normalizeValue(sample.parasite_state);
            case 'nucleic_type': return normalizeValue(sample.nucleic_type);
            case 'nucleic_extraction_protocol': return normalizeValue(sample.nucleic_extraction_protocol);
            case 'sampling_site': return normalizeValue(sample.sampling_site_name);
            default: return normalizeValue(sample.type);
        }
    }

    function buildDistribution(samples, variable) {
        const counts = {};
        (samples || []).forEach((sample) => {
            const aggregate = sample?.aggregateDistributions?.[variable];
            if (aggregate && typeof aggregate === 'object') {
                Object.entries(aggregate).forEach(([label, count]) => {
                    const normalizedLabel = normalizeValue(label);
                    const weight = Number(count) || 0;
                    if (normalizedLabel === 'Unknown' || weight <= 0) return;
                    counts[normalizedLabel] = (counts[normalizedLabel] || 0) + weight;
                });
                return;
            }
            const key = bucketKeyForSample(sample, variable);
            if (key === 'Unknown') return;
            const weight = Number(sample?.weight) || 1;
            counts[key] = (counts[key] || 0) + weight;
        });
        return counts;
    }

    function createPieChartForCluster(distribution, totalCount, variable) {
        const size = 64;
        const radius = 28;
        const centerX = 32;
        const centerY = 32;

        const slices = Object.entries(distribution)
            .filter(([, count]) => count > 0)
            .map(([label, count]) => ({ label, count, color: valueColor(variable, label) }));

        if (slices.length === 0) {
            return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
                <circle cx="${centerX}" cy="${centerY}" r="${radius}" fill="none" stroke="#ccc" stroke-width="2"/>
                <text x="${centerX}" y="${centerY + 6}" text-anchor="middle" font-size="15" font-weight="800" fill="#666"
                    stroke="white" stroke-width="3" paint-order="stroke">${totalCount}</text>
            </svg>`;
        }

        if (slices.length === 1) {
            return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
                <circle cx="${centerX}" cy="${centerY}" r="${radius}" fill="${slices[0].color}" stroke="white" stroke-width="2"/>
                <text x="${centerX}" y="${centerY + 6}" text-anchor="middle" font-size="15" font-weight="800" fill="white"
                    stroke="rgba(0,0,0,0.35)" stroke-width="2" paint-order="stroke">${totalCount}</text>
            </svg>`;
        }

        let currentAngle = 0;
        const total = slices.reduce((sum, item) => sum + item.count, 0);
        let svgPaths = '';

        slices.forEach((item) => {
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
            <text x="${centerX}" y="${centerY + 6}" text-anchor="middle" font-size="15" font-weight="800" fill="#111"
                stroke="white" stroke-width="3" paint-order="stroke">${totalCount}</text>
        </svg>`;
    }

    function setActiveTabButtons(selector, activeKey, dataAttr) {
        document.querySelectorAll(selector).forEach((btn) => {
            btn.classList.toggle('is-active', btn.getAttribute(dataAttr) === activeKey);
        });
    }

    function tabDataByKey(tabs, key) {
        return (tabs || []).find((tab) => tab.key === key) || tabs?.[0] || { key: '', label: '', data: {} };
    }

    function renderPieLegend(labels, colors, values) {
        const container = document.getElementById('pieLegendScroller');
        if (!container) return;
        if (!labels || labels.length === 0) {
            container.innerHTML = '';
            return;
        }
        container.innerHTML = `<div class="inline-flex w-max min-w-full items-center gap-4 pb-1 pr-3">
            ${labels.map((label, idx) => `
                <div class="inline-flex items-center gap-2 text-xs text-gray-700">
                    <span class="w-3 h-3 rounded-full" style="background:${colors[idx]}"></span>
                    <span class="whitespace-nowrap">${label} (${values[idx] ?? 0})</span>
                </div>
            `).join('')}
        </div>`;
    }

    function renderPieTabbedChart(tabKey) {
        const selected = tabDataByKey(window.pieChartTabs || [], tabKey);
        const ctx = document.getElementById('pieTabbedChart');
        if (!ctx || typeof Chart === 'undefined') return;
        const labels = Object.keys(selected.data || {});
        const values = Object.values(selected.data || {});
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (pieChart) pieChart.destroy();
        pieChart = new Chart(ctx, {
            type: 'pie',
            data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
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
        const values = entries.map(([, count]) => count);
        const colors = labels.map((label) => valueColor(selected.key, label));

        if (barChart) barChart.destroy();
        barChart = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ label: selected.label || 'Count', data: values, backgroundColor: colors, borderWidth: 1 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 10,
                            callback(value, index) {
                                const label = labels[index] ?? '';
                                if (selected.key === 'protocol' || selected.key === 'technique_type' || selected.key === 'laboratory' || selected.key === 'nucleic_extraction_protocol') {
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
                                if (selected.key === 'protocol' || selected.key === 'technique_type' || selected.key === 'laboratory' || selected.key === 'nucleic_extraction_protocol') {
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

    function buildTimelineChart(timelineData) {
        const ctx = document.getElementById('timelineChart');
        if (!ctx || typeof Chart === 'undefined') return;
        if (timelineChart) timelineChart.destroy();

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(timelineData || {}),
                datasets: [{
                    label: 'Experiments Tested',
                    data: Object.values(timelineData || {}),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
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

    function refreshLegend(variable) {
        const legend = document.getElementById('mapLegend');
        if (!legend) return;
        const distribution = buildDistribution(aggregatedMapPoints, variable);
        const entries = Object.entries(distribution).sort((a, b) => b[1] - a[1]).slice(0, 12);
        if (entries.length === 0) {
            legend.innerHTML = '<span class="text-xs text-gray-500">No data available for legend.</span>';
            return;
        }
        legend.innerHTML = `
            <div class="mb-2 flex items-center justify-between gap-3">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Legend</span>
                <span class="text-[11px] text-gray-400">Top ${entries.length}</span>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-700">
                ${entries.map(([label, count]) => `
                    <div class="inline-flex max-w-full items-center gap-2">
                        <span class="h-3 w-3 shrink-0 rounded-full ring-1 ring-white" style="background:${valueColor(variable, label)}"></span>
                        <span class="truncate">${label}</span>
                        <span class="text-gray-400">(${count})</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function dominantDistributionLabel(sample, variable) {
        const aggregate = sample?.aggregateDistributions?.[variable];
        if (aggregate && typeof aggregate === 'object') {
            const topEntry = Object.entries(aggregate)
                .filter(([, count]) => (Number(count) || 0) > 0)
                .sort((a, b) => (Number(b[1]) || 0) - (Number(a[1]) || 0))[0];
            if (topEntry?.[0]) {
                return normalizeValue(topEntry[0]);
            }
        }

        const fallback = bucketKeyForSample(sample, variable);
        if (fallback !== 'Unknown') {
            return fallback;
        }

        return normalizeValue(sample?.outcome_discrete);
    }

    function mapCircleRadius(weight) {
        const normalizedWeight = Number(weight) || 1;

        return Math.max(5, Math.min(16, 4 + Math.sqrt(normalizedWeight) * 2.25));
    }

    function renderSampleDetailsList(details) {
        const lines = Array.isArray(details) ? details.filter(Boolean) : [];
        if (lines.length === 0) {
            return '';
        }

        return `<ul class="mt-1 space-y-0.5 text-xs text-gray-600">${lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('')}</ul>`;
    }

    function renderSampleDetailGroups(groups, collapsed = false) {
        const normalizedGroups = Array.isArray(groups)
            ? groups.filter((group) => Array.isArray(group?.details) && group.details.filter(Boolean).length > 0)
            : [];

        if (normalizedGroups.length === 0) {
            return '';
        }

        return normalizedGroups.map((group, index) => {
            const details = group.details.filter(Boolean);
            const visibleDetails = collapsed ? details.slice(0, 2) : details;
            const remaining = details.length - visibleDetails.length;

            return `
                <div class="${index > 0 ? 'mt-2 border-t border-gray-200 pt-2' : ''}">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">${escapeHtml(group.title || 'Details')}</div>
                    <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
                        ${visibleDetails.map((line) => `<li class="${collapsed ? 'truncate' : ''}">${escapeHtml(line)}</li>`).join('')}
                        ${collapsed && remaining > 0 ? `<li class="text-[11px] font-medium text-gray-400">+${remaining} more</li>` : ''}
                    </ul>
                </div>
            `;
        }).join('');
    }

    function renderCollapsedSampleDetails(details, limit = 2) {
        const lines = Array.isArray(details) ? details.filter(Boolean) : [];
        if (lines.length === 0) {
            return '';
        }

        const visible = lines.slice(0, limit);
        const remaining = lines.length - visible.length;

        return `
            <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
                ${visible.map((line) => `<li class="truncate">${escapeHtml(line)}</li>`).join('')}
                ${remaining > 0 ? `<li class="text-[11px] font-medium text-gray-400">+${remaining} more detail${remaining === 1 ? '' : 's'}</li>` : ''}
            </ul>
        `;
    }

    function renderExperimentMeta(entry) {
        const rows = [
            entry?.experiment_code ? `<div><span class="font-semibold">Experiment:</span> ${escapeHtml(entry.experiment_code)}</div>` : '',
            entry?.date_tested ? `<div><span class="font-semibold">Date tested:</span> ${escapeHtml(entry.date_tested)}</div>` : '',
            entry?.outcome_discrete ? `<div><span class="font-semibold">Outcome:</span> ${escapeHtml(entry.outcome_discrete)}</div>` : '',
            entry?.protocol ? `<div><span class="font-semibold">Protocol:</span> ${escapeHtml(entry.protocol)}</div>` : '',
            entry?.technique_type ? `<div><span class="font-semibold">Technique:</span> ${escapeHtml(entry.technique_type)}</div>` : '',
            entry?.pathogen ? `<div><span class="font-semibold">Pathogen:</span> ${escapeHtml(entry.pathogen)}</div>` : '',
            entry?.laboratory ? `<div><span class="font-semibold">Laboratory:</span> ${escapeHtml(entry.laboratory)}</div>` : '',
            entry?.sampling_site_name ? `<div><span class="font-semibold">Sampling site:</span> ${escapeHtml(entry.sampling_site_name)}</div>` : '',
        ].filter(Boolean);

        if (rows.length === 0) {
            return '';
        }

        return `<div class="mt-2 space-y-0.5 text-xs text-gray-700">${rows.join('')}</div>`;
    }

    function flattenClusterEntries(samples) {
        const entries = [];
        (samples || []).forEach((sample) => {
            if (Array.isArray(sample?.entries) && sample.entries.length > 0) {
                sample.entries.forEach((entry) => entries.push(entry));
                return;
            }
            entries.push(sample);
        });

        return entries;
    }

    function summarizeClusterEntries(entries, limit = 6) {
        const preview = (entries || []).slice(0, limit).map((entry) => {
            const code = entry?.experiment_code || entry?.sample_code || 'Unknown';
            const title = entry?.sample_title || entry?.sample_type || 'Sample';
            return `<li class="truncate"><span class="font-medium">${escapeHtml(code)}</span> — ${escapeHtml(title)}</li>`;
        }).join('');

        const remaining = Math.max(0, (entries?.length || 0) - limit);

        return `
            <ul class="mt-2 space-y-0.5 text-xs text-gray-600">${preview}</ul>
            ${remaining > 0 ? `<div class="mt-1 text-[11px] text-gray-400">+${remaining} more experiment${remaining === 1 ? '' : 's'}</div>` : ''}
        `;
    }

    function mapClusterTooltipHtml(samples) {
        const entries = flattenClusterEntries(samples);
        const totalExperiments = entries.length || samples.length;
        const distribution = buildDistribution(samples, currentMapColorVariable);
        const topCategories = Object.entries(distribution).sort((a, b) => b[1] - a[1]).slice(0, 5);
        const siteName = samples.find((sample) => sample?.sampling_site_name)?.sampling_site_name;

        return `
            <div class="max-w-sm">
                <div class="text-sm font-semibold text-gray-900">Cluster summary</div>
                <div class="mt-1 text-xs text-gray-700">${totalExperiments} experiment${totalExperiments === 1 ? '' : 's'} at this map location.</div>
                ${siteName ? `<div class="mt-1 text-xs text-gray-600"><span class="font-semibold">Site:</span> ${escapeHtml(siteName)}</div>` : ''}
                ${topCategories.length > 0 ? `
                    <div class="mt-2 text-xs text-gray-600">
                        <div class="font-semibold text-gray-700">Colored by ${escapeHtml(currentMapColorVariable.replaceAll('_', ' '))}</div>
                        ${topCategories.map(([label, count]) => `<div><span style="color:${valueColor(currentMapColorVariable, label)}">●</span> ${escapeHtml(label)} (${count})</div>`).join('')}
                    </div>
                ` : ''}
                <div class="mt-2 text-[11px] text-gray-400">Click the cluster for full experiment and sample details.</div>
            </div>
        `;
    }

    function mapClusterPopupHtml(samples) {
        const entries = flattenClusterEntries(samples);
        if (entries.length === 0) {
            return `<div class="text-sm text-gray-600">No experiments available for this cluster.</div>`;
        }

        const siteName = samples.find((sample) => sample?.sampling_site_name)?.sampling_site_name;
        const outcomeCounts = {};
        const typeCounts = {};
        entries.forEach((entry) => {
            const outcome = normalizeValue(entry?.outcome_discrete);
            const type = normalizeValue(entry?.sample_type);
            outcomeCounts[outcome] = (outcomeCounts[outcome] || 0) + 1;
            typeCounts[type] = (typeCounts[type] || 0) + 1;
        });

        const header = `
            <div class="mb-3">
                <div class="text-sm font-semibold text-gray-900">Cluster experiments (${entries.length})</div>
                ${siteName ? `<div class="mt-1 text-xs text-gray-600"><span class="font-semibold">Sampling site:</span> ${escapeHtml(siteName)}</div>` : ''}
                <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-gray-600">
                    ${Object.entries(outcomeCounts).map(([label, count]) => `<span class="rounded-full bg-gray-100 px-2 py-0.5">${escapeHtml(label)} (${count})</span>`).join('')}
                </div>
            </div>
        `;

        const body = entries.map((entry, index) => {
            const experimentCode = String(entry.experiment_code || '').trim();
            const experimentLink = experimentCode !== ''
                ? `<a href="${experimentProfileUrl(experimentCode)}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${escapeHtml(experimentCode)}</a>`
                : '<span class="text-gray-500">N/A</span>';
            const detailGroups = Array.isArray(entry.sample_detail_groups) && entry.sample_detail_groups.length > 0
                ? entry.sample_detail_groups
                : [{ title: entry.sample_title || entry.sample_type || 'Sample', details: entry.sample_details || [] }];

            return `
                <div class="${index === 0 ? '' : 'hidden '}rounded-lg border border-gray-200 bg-gray-50 px-3 py-2" data-map-popup-page="${index}">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(entry.sample_title || entry.sample_type || 'Sample')}</div>
                    ${renderSampleDetailGroups(detailGroups, false)}
                    <div class="mt-2 text-xs text-gray-700"><span class="font-semibold">Experiment:</span> ${experimentLink}</div>
                    ${renderExperimentMeta(entry)}
                </div>
            `;
        }).join('');

        const controls = entries.length > 1
            ? `
                <div class="mb-3 flex items-center justify-between gap-3" data-map-popup-controls>
                    <button type="button" class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50" data-map-popup-nav="prev">Previous</button>
                    <span class="text-xs text-gray-500" data-map-popup-counter>1 / ${entries.length}</span>
                    <button type="button" class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50" data-map-popup-nav="next">Next</button>
                </div>
            `
            : '';

        return `
            <div class="max-w-lg" data-map-popup-carousel data-map-popup-total="${entries.length}" data-map-popup-index="0">
                ${header}
                ${controls}
                <div class="max-h-80 space-y-2 overflow-y-auto">${body}</div>
            </div>
        `;
    }

    function bindClusterInteractions(clusterLayer) {
        if (!clusterLayer) {
            return;
        }

        clusterLayer.off('clustermouseover');
        clusterLayer.off('clustermouseout');
        clusterLayer.off('clusterclick');

        clusterLayer.on('clustermouseover', (event) => {
            const markers = event.layer.getAllChildMarkers();
            const samples = markers.map((marker) => marker.sampleData).filter(Boolean);
            event.layer.bindTooltip(mapClusterTooltipHtml(samples), {
                sticky: true,
                direction: 'top',
            }).openTooltip();
        });

        clusterLayer.on('clustermouseout', (event) => {
            event.layer.closeTooltip();
        });

        clusterLayer.on('clusterclick', (event) => {
            const markers = event.layer.getAllChildMarkers();
            const samples = markers.map((marker) => marker.sampleData).filter(Boolean);
            event.layer.bindPopup(mapClusterPopupHtml(samples), {
                maxWidth: 520,
                maxHeight: 480,
            }).openPopup();
        });
    }

    function mapTooltipHtml(sample) {
        const entries = Array.isArray(sample?.entries) ? sample.entries : [];
        if (entries.length <= 1) {
            const entry = entries[0] || {};
            const experimentCode = entry.experiment_code ? escapeHtml(entry.experiment_code) : 'N/A';
            const detailGroups = Array.isArray(entry.sample_detail_groups) && entry.sample_detail_groups.length > 0
                ? entry.sample_detail_groups
                : [{ title: entry.sample_title || sample?.type || 'Sample', details: entry.sample_details || [] }];

            return `
                <div class="max-w-xs">
                    <div class="text-sm font-semibold text-gray-900">${escapeHtml(entry.sample_title || sample?.type || 'Experiment location')}</div>
                    ${renderSampleDetailGroups(detailGroups, true)}
                    <div class="mt-2 text-xs text-gray-700"><span class="font-semibold">Experiment:</span> ${experimentCode}</div>
                    ${entry.outcome_discrete ? `<div class="text-xs text-gray-600">Outcome: ${escapeHtml(entry.outcome_discrete)}</div>` : ''}
                </div>
            `;
        }

        const sampleTitles = [...new Set(entries.map((entry) => String(entry.sample_title || '').trim()).filter(Boolean))];
        const firstTitle = sampleTitles[0] ? escapeHtml(sampleTitles[0]) : 'Samples at this location';
        const remainingSamples = Math.max(0, sampleTitles.length - 1);

        return `
            <div class="max-w-xs">
                <div class="text-sm font-semibold text-gray-900">Aggregated exact-coordinate experiments</div>
                <div class="mt-1 text-xs text-gray-700">${entries.length} experiments aggregated at this exact location.</div>
                <div class="mt-2 text-xs text-gray-600">
                    <span class="font-semibold">Sample preview:</span> ${firstTitle}${remainingSamples > 0 ? ` +${remainingSamples} more` : ''}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">Click to page through the aggregated sample details.</div>
            </div>
        `;
    }

    function mapPopupHtml(sample) {
        const entries = Array.isArray(sample?.entries) ? sample.entries : [];
        if (entries.length === 0) {
            return `<div class="text-sm text-gray-600">No experiments available for this location.</div>`;
        }

        const header = entries.length > 1
            ? `<div class="mb-3 text-sm font-semibold text-gray-900">Aggregated exact-coordinate experiments (${entries.length})</div>`
            : `<div class="mb-3 text-sm font-semibold text-gray-900">${escapeHtml(entries[0].sample_title || sample?.type || 'Experiment location')}</div>`;

        const body = entries.map((entry, index) => {
            const experimentCode = String(entry.experiment_code || '').trim();
            const experimentLink = experimentCode !== ''
                ? `<a href="${experimentProfileUrl(experimentCode)}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${escapeHtml(experimentCode)}</a>`
                : '<span class="text-gray-500">N/A</span>';
            const detailGroups = Array.isArray(entry.sample_detail_groups) && entry.sample_detail_groups.length > 0
                ? entry.sample_detail_groups
                : [{ title: entry.sample_title || entry.sample_type || 'Sample', details: entry.sample_details || [] }];

            return `
                <div class="${index === 0 ? '' : 'hidden '}rounded-lg border border-gray-200 bg-gray-50 px-3 py-2" data-map-popup-page="${index}">
                    <div class="text-sm font-medium text-gray-900">${escapeHtml(entry.sample_title || entry.sample_type || 'Sample')}</div>
                    ${renderSampleDetailGroups(detailGroups, false)}
                    <div class="mt-2 text-xs text-gray-700">
                        <span class="font-semibold">Experiment:</span> ${experimentLink}
                    </div>
                    ${renderExperimentMeta(entry)}
                </div>
            `;
        }).join('');

        const controls = entries.length > 1
            ? `
                <div class="mb-3 flex items-center justify-between gap-3" data-map-popup-controls>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                        data-map-popup-nav="prev"
                    >
                        Previous
                    </button>
                    <span class="text-xs text-gray-500" data-map-popup-counter>1 / ${entries.length}</span>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                        data-map-popup-nav="next"
                    >
                        Next
                    </button>
                </div>
            `
            : '';

        return `
            <div class="max-w-md" data-map-popup-carousel data-map-popup-total="${entries.length}" data-map-popup-index="0">
                ${header}
                ${controls}
                <div class="space-y-2">${body}</div>
            </div>
        `;
    }

    function updateCoordinateCircleStyles() {
        if (!currentCoordinateCircles) {
            return;
        }

        currentCoordinateCircles.eachLayer((layer) => {
            const sample = layer?.sampleData;
            if (!sample || typeof layer.setStyle !== 'function') {
                return;
            }

            const label = dominantDistributionLabel(sample, currentMapColorVariable);
            const color = valueColor(currentMapColorVariable, label);
            layer.setStyle({
                color,
                fillColor: color,
                radius: mapCircleRadius(sample.weight),
            });
        });
    }

    function ensureMapInitialized() {
        const mapContainer = document.getElementById('map');
        if (!mapContainer || typeof L === 'undefined') return false;

        if (currentMap && currentClusters && currentCoordinateCircles) {
            return true;
        }

        mapContainer.innerHTML = '';
        mapContainer._leaflet_id = null;

        currentMap = L.map('map').setView([-28.5595, 22.9375], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
            crossOrigin: true,
        }).addTo(currentMap);

        currentClusters = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            iconCreateFunction: (cluster) => {
                const clusterSamples = cluster.getAllChildMarkers().map((m) => m.sampleData).filter(Boolean);
                let distribution = buildDistribution(clusterSamples, currentMapColorVariable);
                if (Object.keys(distribution).length === 0) {
                    distribution = buildDistribution(clusterSamples, 'outcome');
                }
                const totalWeight = clusterSamples.reduce((sum, sample) => sum + (Number(sample?.weight) || 1), 0);
                return L.divIcon({
                    html: createPieChartForCluster(distribution, totalWeight, currentMapColorVariable),
                    className: 'pie-chart-cluster-icon',
                    iconSize: [64, 64],
                    iconAnchor: [32, 32],
                });
            },
        });

        currentMap.addLayer(currentClusters);
        currentCoordinateCircles = L.layerGroup().addTo(currentMap);
        bindClusterInteractions(currentClusters);

        return true;
    }

    function createClusterLayer(colorVariable) {
        return L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            iconCreateFunction: (cluster) => {
                const clusterSamples = cluster.getAllChildMarkers().map((marker) => marker.sampleData).filter(Boolean);
                let distribution = buildDistribution(clusterSamples, colorVariable);
                if (Object.keys(distribution).length === 0) {
                    distribution = buildDistribution(clusterSamples, 'outcome');
                }

                const totalWeight = clusterSamples.reduce((sum, sample) => sum + (Number(sample?.weight) || 1), 0);

                return L.divIcon({
                    html: createPieChartForCluster(distribution, totalWeight, colorVariable),
                    className: 'pie-chart-cluster-icon',
                    iconSize: [64, 64],
                    iconAnchor: [32, 32],
                });
            },
        });
    }

    function addSamplesToMapLayers(points, clusterLayer, coordinateLayer, colorVariable, bindInteractions = true) {
        (points || []).forEach((sample) => {
            const lat = Number(sample?.latitude);
            const lng = Number(sample?.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const latLng = [lat, lng];
            const marker = L.marker(latLng, {
                icon: L.divIcon({ html: '', className: 'invisible-marker', iconSize: [1, 1] }),
            });

            marker.sampleData = sample;

            if (bindInteractions) {
                marker.bindTooltip(mapTooltipHtml(sample), { sticky: true });
                marker.bindPopup(mapPopupHtml(sample), { maxWidth: 520, maxHeight: 480 });
            }

            clusterLayer?.addLayer(marker);

            if (coordinateLayer) {
                const dominantLabel = dominantDistributionLabel(sample, colorVariable);
                const circleColor = valueColor(colorVariable, dominantLabel);
                const circleMarker = L.circleMarker(latLng, {
                    radius: mapCircleRadius(sample.weight),
                    color: circleColor,
                    weight: 2,
                    opacity: 0.95,
                    fillColor: circleColor,
                    fillOpacity: 0.22,
                });

                circleMarker.sampleData = sample;

                if (bindInteractions) {
                    circleMarker.bindTooltip(mapTooltipHtml(sample), { sticky: true });
                    circleMarker.bindPopup(mapPopupHtml(sample), { maxWidth: 520, maxHeight: 480 });
                }

                coordinateLayer.addLayer(circleMarker);
            }
        });
    }

    function clearRenderedMapPoints() {
        if (currentClusters) {
            currentClusters.clearLayers();
        }
        if (currentCoordinateCircles) {
            currentCoordinateCircles.clearLayers();
        }
    }

    function incrementDistributionCounter(distribution, variable, label, amount = 1) {
        const key = normalizeValue(label);
        if (key === 'Unknown') return;
        if (!distribution[variable]) distribution[variable] = {};
        distribution[variable][key] = (distribution[variable][key] || 0) + amount;
    }

    function aggregateMapPoints(points) {
        const grouped = new Map();
        const siteSummary = new Map();

        (points || []).forEach((point) => {
            const lat = Number(point?.latitude);
            const lng = Number(point?.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            if (point?.sampling_site_id) {
                const siteId = String(point.sampling_site_id);
                const site = siteSummary.get(siteId) || {
                    id: siteId,
                    name: point.sampling_site_name || `Site #${siteId}`,
                    count: 0,
                };
                site.count += 1;
                siteSummary.set(siteId, site);
            }

            const hasSite = point?.sampling_site_id !== null && point?.sampling_site_id !== undefined && point?.sampling_site_id !== '';
            const roundedLat = lat.toFixed(hasSite ? 5 : 3);
            const roundedLng = lng.toFixed(hasSite ? 5 : 3);
            const key = hasSite
                ? `site:${String(point.sampling_site_id)}`
                : `coord:${roundedLat}|${roundedLng}`;

            const row = grouped.get(key) || {
                latitude: Number(roundedLat),
                longitude: Number(roundedLng),
                sampling_site_id: point?.sampling_site_id || null,
                sampling_site_name: point?.sampling_site_name || null,
                aggregateDistributions: {
                    outcome: {},
                    type: {},
                    protocol: {},
                    pathogen: {},
                    technique_type: {},
                    laboratory: {},
                },
                weight: 0,
            };

            row.weight += 1;
            incrementDistributionCounter(row.aggregateDistributions, 'outcome', point?.outcome_discrete, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'type', point?.type, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'protocol', point?.protocol, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'pathogen', point?.pathogen, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'technique_type', point?.technique_type, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'laboratory', point?.laboratory, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'sampling_site', point?.sampling_site_name, 1);

            grouped.set(key, row);
        });

        return {
            groupedPoints: Array.from(grouped.values()),
            samplingSites: siteSummary,
        };
    }

    function accumulatePointsIntoAggregate(grouped, siteSummary, points) {
        (points || []).forEach((point) => {
            const lat = Number(point?.latitude);
            const lng = Number(point?.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            if (point?.sampling_site_id) {
                const siteId = String(point.sampling_site_id);
                const site = siteSummary.get(siteId) || {
                    id: siteId,
                    name: point.sampling_site_name || `Site #${siteId}`,
                    count: 0,
                };
                site.count += 1;
                siteSummary.set(siteId, site);
            }

            const roundedLat = lat.toFixed(6);
            const roundedLng = lng.toFixed(6);
            const siteKey = point?.sampling_site_id ? String(point.sampling_site_id) : '';
            const key = `${roundedLat}|${roundedLng}|${siteKey}`;

            const row = grouped.get(key) || {
                latitude: Number(roundedLat),
                longitude: Number(roundedLng),
                sampling_site_id: point?.sampling_site_id || null,
                sampling_site_name: point?.sampling_site_name || null,
                aggregateDistributions: {
                    outcome: {},
                    type: {},
                    protocol: {},
                    pathogen: {},
                    technique_type: {},
                    laboratory: {},
                },
                weight: 0,
            };

            row.weight += 1;
            incrementDistributionCounter(row.aggregateDistributions, 'outcome', point?.outcome_discrete, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'type', point?.type, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'protocol', point?.protocol, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'pathogen', point?.pathogen, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'technique_type', point?.technique_type, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'laboratory', point?.laboratory, 1);
            incrementDistributionCounter(row.aggregateDistributions, 'sampling_site', point?.sampling_site_name, 1);

            grouped.set(key, row);
        });
    }

    function applyMapVariableStyles() {
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');

        if (currentClusters?.refreshClusters) {
            currentClusters.refreshClusters();
        }

        updateCoordinateCircleStyles();
        refreshLegend(currentMapColorVariable);
    }

    function appendMapPoints(points) {
        const mapContainer = document.getElementById('map');
        if (!mapContainer || typeof L === 'undefined') return;

        if (!ensureMapInitialized()) {
            return;
        }

        addSamplesToMapLayers(points, currentClusters, currentCoordinateCircles, currentMapColorVariable, true);
    }

    function updateSamplingSitesCount() {
        const el = document.getElementById('samplingSitesCount');
        if (!el) return;
        el.textContent = String(samplingSitesSummary.size);
    }

    function updateSamplingSitesModal() {
        const tbody = document.getElementById('samplingSitesModalBody');
        if (!tbody) return;

        const rows = Array.from(samplingSitesSummary.values()).sort((a, b) => {
            if (b.count !== a.count) return b.count - a.count;
            return String(a.name).localeCompare(String(b.name));
        });
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td class="px-4 py-3 text-sm text-gray-500" colspan="2">No sampling sites found in the filtered dataset.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map((r) => {
            const name = String(r.name || `Site #${r.id}`).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
            return `<tr class="hover:bg-gray-50">
                <td class="px-4 py-2 text-sm font-medium text-gray-900">${name}</td>
                <td class="px-4 py-2 text-sm text-gray-500">${r.count}</td>
            </tr>`;
        }).join('');
    }

    function buildMapCacheKey(baseUrl, filters) {
        const params = new URLSearchParams();
        Object.entries(filters || {}).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') return;
            params.set(key, String(value));
        });

        return `${MAP_PAYLOAD_CACHE_PREFIX}${baseUrl}?${params.toString()}`;
    }

    function readCachedMapPayload(cacheKey) {
        try {
            const raw = window.sessionStorage.getItem(cacheKey);
            if (!raw) {
                return null;
            }

            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                return null;
            }

            return parsed;
        } catch (error) {
            return null;
        }
    }

    function writeCachedMapPayload(cacheKey, payload) {
        try {
            window.sessionStorage.setItem(cacheKey, JSON.stringify(payload));
        } catch (error) {
            // Ignore storage quota/cache serialization failures.
        }
    }

    function applyMapPayload(payload) {
        const groupedPoints = Array.isArray(payload?.grouped_points) ? payload.grouped_points : [];
        const siteSummaryRows = Array.isArray(payload?.sampling_sites_summary) ? payload.sampling_sites_summary : [];
        const nextSiteSummary = new Map();

        siteSummaryRows.forEach((row) => {
            if (!row?.id) return;
            nextSiteSummary.set(String(row.id), {
                id: String(row.id),
                name: row.name || `Site #${row.id}`,
                count: Number(row.count) || 0,
            });
        });

        clearRenderedMapPoints();
        samplingSitesSummary = nextSiteSummary;
        aggregatedMapPoints = groupedPoints;
        appendMapPoints(aggregatedMapPoints);
        updateSamplingSitesCount();
        updateSamplingSitesModal();
        applyMapVariableStyles();
    }

    function buildGroupedPointKey(row) {
        const lat = Number(row?.latitude);
        const lng = Number(row?.longitude);
        const siteId = row?.sampling_site_id ? String(row.sampling_site_id) : 'none';

        return `coord:${lat.toFixed(6)}|${lng.toFixed(6)}|site:${siteId}`;
    }

    function mergeDistributionCountMaps(target, source) {
        Object.entries(source || {}).forEach(([label, count]) => {
            const weight = Number(count) || 0;
            if (weight <= 0) {
                return;
            }

            target[label] = (target[label] || 0) + weight;
        });
    }

    function cloneGroupedMapRow(row) {
        const aggregateDistributions = {};

        Object.entries(row?.aggregateDistributions || {}).forEach(([variable, counts]) => {
            aggregateDistributions[variable] = { ...(counts || {}) };
        });

        return {
            latitude: row.latitude,
            longitude: row.longitude,
            sampling_site_id: row.sampling_site_id ?? null,
            sampling_site_name: row.sampling_site_name ?? null,
            weight: Number(row.weight) || 0,
            aggregateDistributions,
            entries: Array.isArray(row.entries) ? [...row.entries] : [],
        };
    }

    function mergeGroupedMapRows(aggregateMap, rows) {
        (rows || []).forEach((row) => {
            const key = buildGroupedPointKey(row);
            const existing = aggregateMap.get(key);

            if (!existing) {
                aggregateMap.set(key, cloneGroupedMapRow(row));
                return;
            }

            existing.weight += Number(row.weight) || 0;

            Object.entries(row?.aggregateDistributions || {}).forEach(([variable, counts]) => {
                if (!existing.aggregateDistributions[variable]) {
                    existing.aggregateDistributions[variable] = {};
                }

                mergeDistributionCountMaps(existing.aggregateDistributions[variable], counts);
            });

            if (Array.isArray(row.entries) && row.entries.length > 0) {
                existing.entries = (existing.entries || []).concat(row.entries);
            }
        });
    }

    function mergeSamplingSitesSummaryRows(summaryMap, rows) {
        (rows || []).forEach((row) => {
            if (!row?.id) {
                return;
            }

            const id = String(row.id);
            const existing = summaryMap.get(id) || {
                id,
                name: row.name || `Site #${id}`,
                count: 0,
            };

            existing.count += Number(row.count) || 0;
            summaryMap.set(id, existing);
        });
    }

    function buildMapPayloadFromAggregate(groupedMap, summaryMap) {
        return {
            grouped_points: Array.from(groupedMap.values()),
            sampling_sites_summary: Array.from(summaryMap.values()),
        };
    }

    function showMapLoading(percent, message = 'Loading map data...', detail = '') {
        const overlay = document.getElementById('mapLoadingOverlay');
        const progressBar = document.getElementById('mapLoadingProgressBar');
        const percentEl = document.getElementById('mapLoadingPercent');
        const messageEl = document.getElementById('mapLoadingMessage');
        const detailEl = document.getElementById('mapLoadingDetail');

        if (!overlay || !progressBar || !percentEl || !messageEl) {
            return;
        }

        if (mapLoadingHideTimer) {
            clearTimeout(mapLoadingHideTimer);
            mapLoadingHideTimer = null;
        }

        overlay.classList.add('is-visible');
        overlay.classList.remove('hidden');
        messageEl.textContent = message;

        if (detailEl) {
            detailEl.textContent = detail;
        }

        const hasPercent = Number.isFinite(percent);
        overlay.classList.toggle('is-indeterminate', !hasPercent);

        if (hasPercent) {
            const clamped = Math.max(0, Math.min(100, Math.round(percent)));
            progressBar.style.width = `${clamped}%`;
            percentEl.textContent = `${clamped}%`;
            return;
        }

        progressBar.style.width = '35%';
        percentEl.textContent = 'Loading...';
    }

    function hideMapLoading(delayMs = 250) {
        const overlay = document.getElementById('mapLoadingOverlay');

        if (!overlay) {
            return;
        }

        if (mapLoadingHideTimer) {
            clearTimeout(mapLoadingHideTimer);
        }

        mapLoadingHideTimer = setTimeout(() => {
            overlay.classList.remove('is-visible', 'is-indeterminate');
            overlay.classList.add('hidden');
            mapLoadingHideTimer = null;
        }, delayMs);
    }

    function buildMapRequestParams(filters, extra = {}) {
        const params = new URLSearchParams();

        Object.entries(filters || {}).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                return;
            }

            params.set(key, String(value));
        });

        Object.entries(extra || {}).forEach(([key, value]) => {
            if (value === null || value === undefined || value === '') {
                return;
            }

            params.set(key, String(value));
        });

        return params;
    }

    async function fetchMapChunk(baseUrl, filters, afterId, includeTotal) {
        const params = buildMapRequestParams(filters, {
            chunked: '1',
            chunk_size: String(MAP_CHUNK_SIZE),
            after_id: String(afterId),
            include_total: includeTotal ? '1' : '0',
        });

        const response = await fetch(`${baseUrl}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(`Map request failed with status ${response.status}`);
        }

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('Map request did not return JSON');
        }

        return response.json();
    }

    async function loadAllMapPoints() {
        const baseUrl = window.mapPointsUrl;
        if (!baseUrl) return;

        mapLoadToken++;
        const token = mapLoadToken;
        const filters = window.activeFilters || {};
        const cacheKey = buildMapCacheKey(baseUrl, filters);

        const cachedPayload = readCachedMapPayload(cacheKey);
        if (cachedPayload) {
            applyMapPayload(cachedPayload);
        }

        showMapLoading(cachedPayload ? 12 : 0, 'Loading map data...');

        try {
            let afterId = 0;
            let total = null;
            let loadedExperiments = 0;
            const groupedMap = new Map();
            const summaryMap = new Map();

            while (true) {
                const payload = await fetchMapChunk(baseUrl, filters, afterId, afterId === 0);
                if (token !== mapLoadToken) {
                    return;
                }

                const meta = payload?.meta || {};
                if (total === null && Number.isFinite(Number(meta.total))) {
                    total = Number(meta.total);
                }

                loadedExperiments += Number(meta.chunk_count) || 0;
                mergeGroupedMapRows(groupedMap, payload?.grouped_points || []);
                mergeSamplingSitesSummaryRows(summaryMap, payload?.sampling_sites_summary || []);

                let progress = null;
                if (total !== null && total > 0) {
                    progress = Math.min(99, Math.round((loadedExperiments / total) * 100));
                } else if (loadedExperiments > 0) {
                    progress = Math.min(90, 20 + Math.floor(loadedExperiments / MAP_CHUNK_SIZE) * 10);
                }

                const detail = total !== null && total > 0
                    ? `${loadedExperiments.toLocaleString()} of ${total.toLocaleString()} experiments`
                    : (loadedExperiments > 0 ? `${loadedExperiments.toLocaleString()} experiments loaded` : 'Preparing map...');

                showMapLoading(progress, 'Loading map data...', detail);

                if (meta.complete) {
                    break;
                }

                const nextAfterId = Number(meta.next_after_id ?? meta.last_id ?? 0);
                if (!nextAfterId || nextAfterId === afterId) {
                    break;
                }

                afterId = nextAfterId;
            }

            const finalPayload = buildMapPayloadFromAggregate(groupedMap, summaryMap);
            if (token !== mapLoadToken) {
                return;
            }

            showMapLoading(100, 'Map ready', total !== null && total > 0
                ? `${Math.min(loadedExperiments, total).toLocaleString()} experiments loaded`
                : `${loadedExperiments.toLocaleString()} experiments loaded`);

            writeCachedMapPayload(cacheKey, finalPayload);
            applyMapPayload(finalPayload);
            hideMapLoading(400);
        } catch (error) {
            console.error('Failed loading map points', error);
            if (token !== mapLoadToken) {
                return;
            }

            if (cachedPayload) {
                hideMapLoading(0);
                return;
            }

            clearRenderedMapPoints();
            samplingSitesSummary = new Map();
            aggregatedMapPoints = [];
            updateSamplingSitesCount();
            updateSamplingSitesModal();
            applyMapVariableStyles();
            showMapLoading(0, 'Failed to load map data', 'Please try again or adjust filters.');
            hideMapLoading(1800);
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

    function resolveFormatButton(target, referenceButton = null) {
        if (referenceButton?.matches?.(`[data-format-target="${target}"]`)) {
            return referenceButton;
        }

        const scopedButton = referenceButton
            ?.closest('.dashboard-box-tools')
            ?.querySelector(`[data-format-target="${target}"]`);
        if (scopedButton) {
            return scopedButton;
        }

        return [...document.querySelectorAll(`[data-format-target="${target}"]`)]
            .find((button) => button.isConnected && !button.closest('[data-export-clone-sandbox]')) || null;
    }

    function syncFormatButtonLabel(button) {
        if (!button) {
            return;
        }

        const format = String(button.getAttribute('data-format') || 'png').toLowerCase();
        let label = button.querySelector('[data-format-label]');
        if (!label) {
            label = document.createElement('span');
            label.setAttribute('data-format-label', '1');
            label.className = 'ml-1 text-[10px] font-semibold uppercase tracking-wide';
            button.appendChild(label);
        }

        label.textContent = format;
        button.setAttribute('title', `Format: ${format} (click to change)`);
        button.setAttribute('aria-label', `Format ${format}`);
    }

    function initializeFormatButtons() {
        document.querySelectorAll('[data-format-target]').forEach((button) => {
            syncFormatButtonLabel(button);
        });
    }

    function currentFormatFor(target, referenceButton = null) {
        const btn = resolveFormatButton(target, referenceButton);
        const value = btn?.getAttribute('data-format') || 'png';
        return EXPORT_FORMATS.includes(value) ? value : 'png';
    }

    function cycleFormatFor(target, referenceButton = null) {
        const btn = resolveFormatButton(target, referenceButton);
        if (!btn) return;
        const idx = EXPORT_FORMATS.indexOf(currentFormatFor(target, btn));
        const next = EXPORT_FORMATS[(idx + 1) % EXPORT_FORMATS.length];
        btn.setAttribute('data-format', next);
        syncFormatButtonLabel(btn);
    }

    function setTemporaryInlineStyles(node, styles, cleanup) {
        if (!node) {
            return;
        }

        const originalStyle = node.getAttribute('style');
        Object.entries(styles).forEach(([property, value]) => {
            node.style[property] = value;
        });

        cleanup.push(() => {
            if (originalStyle === null) {
                node.removeAttribute('style');
            } else {
                node.setAttribute('style', originalStyle);
            }
        });
    }

    async function captureElementCanvas(element, prepare = null) {
        if (!element || typeof html2canvas === 'undefined') {
            return null;
        }

        const cleanup = [];

        try {
            if (typeof prepare === 'function') {
                prepare(cleanup);
            }

            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

            const rect = element.getBoundingClientRect();
            const width = Math.ceil(Math.max(rect.width, element.scrollWidth || 0, 1));
            const height = Math.ceil(Math.max(rect.height, element.scrollHeight || 0, 1));

            return await html2canvas(element, {
                useCORS: true,
                backgroundColor: '#ffffff',
                scale: 2,
                width,
                height,
                windowWidth: Math.max(document.documentElement.clientWidth, width + 80),
                windowHeight: Math.max(document.documentElement.clientHeight, height + 80),
                scrollX: 0,
                scrollY: -window.scrollY,
            });
        } finally {
            while (cleanup.length) {
                const restore = cleanup.pop();
                restore();
            }
        }
    }

    function computeExportDimensions(element) {
        const rootRect = element.getBoundingClientRect();
        let width = Math.ceil(Math.max(rootRect.width, element.scrollWidth, 720));
        let height = Math.ceil(Math.max(rootRect.height, element.scrollHeight, 480));

        element.querySelectorAll('.h-72, .h-96, #pieLegendScroller, #mapLegend, #map, canvas').forEach((node) => {
            const rect = node.getBoundingClientRect();
            const relativeRight = Math.ceil(rect.left - rootRect.left + rect.width);
            const relativeBottom = Math.ceil(rect.top - rootRect.top + rect.height);

            width = Math.max(width, relativeRight, node.scrollWidth || 0);
            height = Math.max(height, relativeBottom, node.scrollHeight || 0);
        });

        return {
            width: width + 24,
            height: height + 24,
        };
    }

    function prepareClonedExportElement(sourceElement, clonedElement) {
        if (!sourceElement || !clonedElement) {
            return;
        }

        const { width } = computeExportDimensions(sourceElement);

        clonedElement.style.width = `${width}px`;
        clonedElement.style.maxWidth = 'none';
        clonedElement.style.height = 'auto';
        clonedElement.style.overflow = 'visible';
        clonedElement.style.paddingRight = '12px';
        clonedElement.style.paddingBottom = '12px';
        clonedElement.style.background = '#ffffff';

        clonedElement.querySelectorAll('.dashboard-box-tools').forEach((node) => node.remove());

        const sourceSizedSections = sourceElement.querySelectorAll('.h-72, .h-96');
        clonedElement.querySelectorAll('.h-72, .h-96').forEach((node, index) => {
            const sourceNode = sourceSizedSections[index];
            const expandedHeight = Math.ceil(Math.max(
                sourceNode?.scrollHeight || 0,
                sourceNode?.getBoundingClientRect().height || 0,
                320,
            ));

            node.style.height = `${expandedHeight}px`;
            node.style.minHeight = `${expandedHeight}px`;
            node.style.overflow = 'visible';
        });

        const sourcePieLegend = sourceElement.querySelector('#pieLegendScroller');
        const clonedPieLegend = clonedElement.querySelector('#pieLegendScroller');
        if (sourcePieLegend && clonedPieLegend) {
            clonedPieLegend.style.overflow = 'visible';
            clonedPieLegend.style.maxWidth = 'none';
            clonedPieLegend.style.width = `${Math.ceil(Math.max(
                sourcePieLegend.scrollWidth,
                sourcePieLegend.getBoundingClientRect().width,
                width,
            ))}px`;
        }

        const sourceMapLegend = sourceElement.querySelector('#mapLegend');
        const clonedMapLegend = clonedElement.querySelector('#mapLegend');
        if (sourceMapLegend && clonedMapLegend) {
            clonedMapLegend.style.overflow = 'visible';
            clonedMapLegend.style.maxHeight = 'none';
            clonedMapLegend.style.maxWidth = 'none';
            clonedMapLegend.style.width = `${Math.ceil(Math.max(
                sourceMapLegend.scrollWidth,
                sourceMapLegend.getBoundingClientRect().width,
            ))}px`;
        }

        const sourceMap = sourceElement.querySelector('#map');
        const clonedMap = clonedElement.querySelector('#map');
        if (sourceMap && clonedMap) {
            const sourceMapRect = sourceMap.getBoundingClientRect();
            clonedMap.style.width = `${Math.ceil(sourceMapRect.width)}px`;
            clonedMap.style.height = `${Math.ceil(sourceMapRect.height)}px`;
            clonedMap.style.overflow = 'hidden';
        }

        const sourceCanvases = sourceElement.querySelectorAll('canvas');
        const clonedCanvases = clonedElement.querySelectorAll('canvas');
        sourceCanvases.forEach((sourceCanvas, index) => {
            const clonedCanvas = clonedCanvases[index];
            if (!clonedCanvas) {
                return;
            }

            const rect = sourceCanvas.getBoundingClientRect();
            clonedCanvas.width = sourceCanvas.width;
            clonedCanvas.height = sourceCanvas.height;
            clonedCanvas.style.width = `${Math.ceil(rect.width)}px`;
            clonedCanvas.style.height = `${Math.ceil(rect.height)}px`;

            const context = clonedCanvas.getContext('2d');
            if (context) {
                context.clearRect(0, 0, clonedCanvas.width, clonedCanvas.height);
                context.drawImage(sourceCanvas, 0, 0);
            }
        });
    }

    async function exportElement(element, format, fileName) {
        if (!element || typeof html2canvas === 'undefined') return;
        let canvas;
        const { width, height } = computeExportDimensions(element);

        try {
            await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

            canvas = await html2canvas(element, {
                useCORS: true,
                backgroundColor: '#ffffff',
                scale: 2,
                width,
                height,
                windowWidth: Math.max(document.documentElement.clientWidth, width + 120),
                windowHeight: Math.max(document.documentElement.clientHeight, height + 120),
                scrollX: 0,
                scrollY: -window.scrollY,
                removeContainer: true,
                ignoreElements: (node) => !!node.closest?.('.dashboard-box-tools'),
                onclone(clonedDocument) {
                    const clonedElement = element.id ? clonedDocument.getElementById(element.id) : null;
                    prepareClonedExportElement(element, clonedElement);
                },
            });
        } catch (error) {
            console.error('Experiments export failed', error);
        }

        if (!canvas) {
            return;
        }
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

    async function exportMapContent(format, fileName) {
        const mapNode = document.getElementById('map');
        const legendNode = document.getElementById('mapLegend');
        const titleNode = document.querySelector('#mapContent .map-inline-title');
        const activeTabNode = document.querySelector('#mapVariableTabs [data-map-tab].is-active');

        if (!mapNode || typeof html2canvas === 'undefined' || typeof L === 'undefined') {
            return;
        }

        const sourceRect = mapNode.getBoundingClientRect();
        const exportHost = document.createElement('div');
        exportHost.style.position = 'fixed';
        exportHost.style.left = '-100000px';
        exportHost.style.top = '0';
        exportHost.style.width = `${Math.ceil(sourceRect.width)}px`;
        exportHost.style.height = `${Math.ceil(sourceRect.height)}px`;
        exportHost.style.background = '#ffffff';
        exportHost.style.zIndex = '-1';
        exportHost.style.pointerEvents = 'none';
        document.body.appendChild(exportHost);

        let exportMap = null;
        let exportTileLayer = null;
        let mapCanvas = null;

        try {
            exportMap = L.map(exportHost, {
                zoomControl: false,
                attributionControl: false,
                preferCanvas: false,
                zoomAnimation: false,
                fadeAnimation: false,
                markerZoomAnimation: false,
                inertia: false,
            });

            const center = currentMap ? currentMap.getCenter() : L.latLng(-28.5595, 22.9375);
            const zoom = currentMap ? currentMap.getZoom() : 5;

            exportMap.setView(center, zoom, { animate: false });

            exportTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors',
                crossOrigin: true,
                updateWhenIdle: true,
                updateWhenZooming: false,
                keepBuffer: 4,
            }).addTo(exportMap);

            const exportClusters = createClusterLayer(currentMapColorVariable);
            const exportCircles = L.layerGroup().addTo(exportMap);
            exportMap.addLayer(exportClusters);
            addSamplesToMapLayers(aggregatedMapPoints, exportClusters, exportCircles, currentMapColorVariable, false);

            exportMap.invalidateSize(false);
            if (exportClusters?.refreshClusters) {
                exportClusters.refreshClusters();
            }

            await new Promise((resolve) => setTimeout(resolve, 450));

            mapCanvas = await captureElementCanvas(exportHost, (cleanup) => {
                setTemporaryInlineStyles(exportHost, {
                    overflow: 'hidden',
                    borderRadius: '12px',
                }, cleanup);
            });
        } finally {
            if (exportMap) {
                exportMap.remove();
            }
            exportHost.remove();
        }

        if (!mapCanvas) {
            return;
        }

        let legendCanvas = null;
        if (legendNode && legendNode.textContent.trim() !== '') {
            legendCanvas = await captureElementCanvas(legendNode, (cleanup) => {
                setTemporaryInlineStyles(legendNode, {
                    position: 'static',
                    inset: 'auto',
                    marginLeft: '0',
                    maxWidth: 'none',
                    maxHeight: 'none',
                    width: `${Math.ceil(Math.max(legendNode.scrollWidth, legendNode.getBoundingClientRect().width, 320))}px`,
                    overflow: 'visible',
                    pointerEvents: 'auto',
                }, cleanup);

                legendNode.querySelectorAll('.truncate').forEach((node) => {
                    setTemporaryInlineStyles(node, {
                        overflow: 'visible',
                        textOverflow: 'clip',
                        whiteSpace: 'normal',
                        display: 'inline',
                        maxWidth: 'none',
                    }, cleanup);
                });
            });
        }

        const scale = 2;
        const padding = 24 * scale;
        const titleHeight = titleNode ? 18 * scale : 0;
        const subtitleHeight = activeTabNode ? 16 * scale : 0;
        const headerHeight = titleHeight + subtitleHeight + (titleNode || activeTabNode ? 20 * scale : 0);
        const legendSpacing = legendCanvas ? 18 * scale : 0;
        const finalWidth = Math.max(
            mapCanvas.width + padding * 2,
            legendCanvas ? legendCanvas.width + padding * 2 : 0,
        );
        const finalHeight = headerHeight + mapCanvas.height + (legendCanvas ? legendCanvas.height + legendSpacing : 0) + padding * 2;
        const finalCanvas = document.createElement('canvas');

        finalCanvas.width = finalWidth;
        finalCanvas.height = finalHeight;

        const context = finalCanvas.getContext('2d');
        if (!context) {
            return;
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, finalWidth, finalHeight);

        let cursorY = padding;

        if (titleNode) {
            context.fillStyle = '#111827';
            context.font = `700 ${18 * scale}px ui-sans-serif, system-ui, sans-serif`;
            context.fillText(titleNode.textContent.trim(), padding, cursorY + (14 * scale));
            cursorY += 22 * scale;
        }

        if (activeTabNode) {
            context.fillStyle = '#4b5563';
            context.font = `${12 * scale}px ui-sans-serif, system-ui, sans-serif`;
            context.fillText(`Legend variable: ${activeTabNode.textContent.trim()}`, padding, cursorY + (10 * scale));
            cursorY += 18 * scale;
        }

        if (headerHeight > 0) {
            cursorY += 8 * scale;
        }

        context.drawImage(mapCanvas, padding, cursorY);
        cursorY += mapCanvas.height;

        if (legendCanvas) {
            cursorY += legendSpacing;
            context.drawImage(legendCanvas, padding, cursorY);
        }

        const lower = String(format || 'png').toLowerCase();
        const mime = (lower === 'jpg' || lower === 'jpeg') ? 'image/jpeg' : 'image/png';
        const dataUrl = finalCanvas.toDataURL(mime, 0.95);

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
        if (placeholder?.parentNode) {
            placeholder.parentNode.insertBefore(content, placeholder);
            placeholder.remove();
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

    function refreshAll() {
        const pieTabs = window.pieChartTabs || [];
        const barTabs = window.barChartTabs || [];
        const mapTabs = window.mapColorVariableOptions || [];
        renderPieTabbedChart(pieTabs[0]?.key || 'outcome');
        renderBarTabbedChart(barTabs[0]?.key || 'protocol');
        buildTimelineChart(window.timelineData || {});
        currentMapColorVariable = mapTabs[0]?.key || 'outcome';
        setActiveTabButtons('[data-map-tab]', currentMapColorVariable, 'data-map-tab');
        loadAllMapPoints();
    }

    function scheduleRefresh(delay = 120) {
        if (refreshTimer) {
            clearTimeout(refreshTimer);
        }
        refreshTimer = setTimeout(refreshAll, delay);
    }

    document.addEventListener('click', function (event) {
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
            currentMapColorVariable = mapTab.getAttribute('data-map-tab') || 'outcome';
            applyMapVariableStyles();
            return;
        }

        const popupNavBtn = event.target.closest('[data-map-popup-nav]');
        if (popupNavBtn) {
            event.preventDefault();
            const carousel = popupNavBtn.closest('[data-map-popup-carousel]');
            if (!carousel) {
                return;
            }

            const total = Number(carousel.getAttribute('data-map-popup-total') || 0);
            if (total <= 1) {
                return;
            }

            const currentIndex = Number(carousel.getAttribute('data-map-popup-index') || 0);
            const direction = popupNavBtn.getAttribute('data-map-popup-nav');
            const nextIndex = direction === 'prev'
                ? (currentIndex - 1 + total) % total
                : (currentIndex + 1) % total;

            carousel.setAttribute('data-map-popup-index', String(nextIndex));
            carousel.querySelectorAll('[data-map-popup-page]').forEach((pageEl) => {
                const pageIndex = Number(pageEl.getAttribute('data-map-popup-page') || 0);
                pageEl.classList.toggle('hidden', pageIndex !== nextIndex);
            });

            const counter = carousel.querySelector('[data-map-popup-counter]');
            if (counter) {
                counter.textContent = `${nextIndex + 1} / ${total}`;
            }
            return;
        }

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
            cycleFormatFor(formatBtn.getAttribute('data-format-target'), formatBtn);
            return;
        }

        const downloadBtn = event.target.closest('[data-download-target]');
        if (downloadBtn) {
            event.preventDefault();
            const target = downloadBtn.getAttribute('data-download-target');
            if (target === 'pie') exportElement(document.getElementById('pieContent'), currentFormatFor('pie', downloadBtn), 'experiments-pie-chart');
            if (target === 'bar') exportElement(document.getElementById('barContent'), currentFormatFor('bar', downloadBtn), 'experiments-bar-chart');
            if (target === 'map') exportMapContent(currentFormatFor('map', downloadBtn), 'experiments-map');
        }
    });

    Livewire.on('filtersUpdated', (payload) => {
        closeExpandedContent();
        const data = payload?.data || {};
        if (data.descriptive_stats?.testing_timeline) window.timelineData = data.descriptive_stats.testing_timeline;
        if (data.pieChartTabs) window.pieChartTabs = data.pieChartTabs;
        if (data.barChartTabs) window.barChartTabs = data.barChartTabs;
        if (data.mapColorVariableOptions) window.mapColorVariableOptions = data.mapColorVariableOptions;
        if (data.mapPointsUrl) window.mapPointsUrl = data.mapPointsUrl;
        if (data.activeFilters) window.activeFilters = data.activeFilters;
        scheduleRefresh(120);
    });

    function bootstrap() {
        if (!(document.getElementById('pieTabbedChart') && document.getElementById('barTabbedChart') && document.getElementById('map'))) {
            setTimeout(bootstrap, 180);
            return;
        }
        if (typeof Chart === 'undefined' || typeof L === 'undefined') {
            setTimeout(bootstrap, 180);
            return;
        }
        registerLivewireDashboardHooks();
        scheduleDashboardFilterDatalists(0);
        initializeFormatButtons();
        refreshAll();
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeExpandedContent();
        }
    });
    document.addEventListener('livewire:init', registerLivewireDashboardHooks);
    document.addEventListener('DOMContentLoaded', bootstrap);
    document.addEventListener('livewire:initialized', () => setTimeout(bootstrap, 180));
    document.addEventListener('livewire:navigated', () => setTimeout(bootstrap, 180));
    window.addEventListener('load', () => setTimeout(bootstrap, 180));
})();
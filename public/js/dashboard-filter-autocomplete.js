(function () {
    'use strict';

    if (window.__dashboardFilterAutocompleteLoaded) {
        return;
    }
    window.__dashboardFilterAutocompleteLoaded = true;

    const STYLE_ID = 'dashboard-filter-autocomplete-styles';
    let refreshTimer = null;

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            [data-dashboard-filter-root] [data-dashboard-datalist-shell] {
                position: relative;
            }

            [data-dashboard-filter-root] .dashboard-autocomplete-panel {
                scrollbar-width: thin;
                scrollbar-color: rgba(148, 163, 184, 0.75) transparent;
            }

            [data-dashboard-filter-root] .dashboard-autocomplete-panel::-webkit-scrollbar {
                width: 8px;
            }

            [data-dashboard-filter-root] .dashboard-autocomplete-panel::-webkit-scrollbar-track {
                background: transparent;
            }

            [data-dashboard-filter-root] .dashboard-autocomplete-panel::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, 0.8);
                border-radius: 9999px;
            }

            [data-dashboard-filter-root] .dashboard-autocomplete-option {
                border: 0;
                background: transparent;
            }
        `;

        document.head.appendChild(style);
    }

    function getDashboardRoots() {
        return [...document.querySelectorAll('[data-dashboard-filter-root]')];
    }

    function getDashboardComponent(root) {
        const componentEl = root ? root.closest('[wire\\:id]') : null;
        const componentId = componentEl ? componentEl.getAttribute('wire:id') : null;
        if (!componentId || !window.Livewire?.find) {
            return null;
        }

        return window.Livewire.find(componentId);
    }

    function getWireModelName(select) {
        const wireModelAttribute = [...select.attributes].find((attribute) => attribute.name.startsWith('wire:model'));

        return String(wireModelAttribute?.value || '').trim();
    }

    function normalizeSelectableValue(value) {
        const normalized = String(value ?? '').trim();

        return normalized.toLowerCase() === 'all' ? '' : normalized;
    }

    function resolveCurrentDisplay(select, root) {
        const currentValue = normalizeSelectableValue(select.value);
        if (currentValue === '') {
            const propName = getWireModelName(select);
            const component = getDashboardComponent(root);
            if (component && propName) {
                const liveValue = normalizeSelectableValue(component.get(propName));
                if (liveValue !== '') {
                    const liveMatch = [...select.options].find((option) => normalizeSelectableValue(option.value) === liveValue);

                    return liveMatch ? String(liveMatch.textContent || '').trim() : liveValue;
                }
            }

            return '';
        }

        const match = [...select.options].find((option) => normalizeSelectableValue(option.value) === currentValue);

        return match ? String(match.textContent || '').trim() : currentValue;
    }

    function syncSelectFromComponent(root, select) {
        const propName = getWireModelName(select);
        const component = getDashboardComponent(root);
        if (!component || propName === '') {
            return;
        }

        const liveValue = normalizeSelectableValue(component.get(propName));
        const selectValue = normalizeSelectableValue(select.value);

        if (liveValue !== selectValue) {
            applySelectValue(select, liveValue);
        }
    }

    function resolveSelectedValue(select, displayValue) {
        const normalizedDisplayValue = String(displayValue ?? '').trim();
        if (normalizedDisplayValue === '') {
            return '';
        }

        const options = [...select.options];
        const byText = options.find((option) => String(option.textContent || '').trim().toLowerCase() === normalizedDisplayValue.toLowerCase());
        if (byText) {
            return normalizeSelectableValue(byText.value);
        }

        const byValue = options.find((option) => String(option.value || '').trim().toLowerCase() === normalizedDisplayValue.toLowerCase());
        if (byValue) {
            return normalizeSelectableValue(byValue.value);
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

    function applySelectValue(select, resolvedValue) {
        const normalizedValue = normalizeSelectableValue(resolvedValue);
        const options = [...select.options];
        const directMatch = options.find((option) => normalizeSelectableValue(option.value) === normalizedValue);

        if (directMatch) {
            select.value = directMatch.value;
            return;
        }

        if (normalizedValue === '') {
            const allOption = options.find((option) => normalizeSelectableValue(option.value) === '');
            if (allOption) {
                select.value = allOption.value;
                return;
            }
        }

        select.value = normalizedValue;
    }

    function buildDashboardFilterAutocomplete(root, select) {
        const propName = getWireModelName(select);
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

        syncSelectFromComponent(root, select);

        const firstOption = select.querySelector('option');
        const inputClassName = select.className.replace(/\bhidden\b/g, '').trim();
        const currentValue = normalizeSelectableValue(select.value);
        const currentDisplayValue = resolveCurrentDisplay(select, root);
        const defaultOptionLabel = String(firstOption?.textContent || 'Type to filter').trim();
        const usePlaceholderOnly = select.dataset.dashboardPlaceholderCurrent === 'true' && currentValue === '';
        const placeholder = escapeHtml(defaultOptionLabel);
        const renderedInputValue = usePlaceholderOnly ? '' : currentDisplayValue;
        const optionLabels = [...new Set(
            [...select.options]
                .filter((option) => normalizeSelectableValue(option.value) !== '')
                .map((option) => String(option.textContent || option.value || '').trim())
                .filter(Boolean),
        )];

        shell.innerHTML = `
            <div class="relative" data-dashboard-autocomplete="${propName}">
                <input
                    type="text"
                    value="${escapeHtml(renderedInputValue)}"
                    placeholder="${placeholder}"
                    class="${`${inputClassName} bg-white`.trim()}"
                    data-dashboard-datalist-input="${propName}"
                    autocomplete="off"
                    spellcheck="false"
                    ${select.disabled ? 'disabled' : ''}
                >
                <div
                    class="dashboard-autocomplete-panel absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-1 shadow-xl"
                    data-dashboard-autocomplete-panel="${propName}"
                ></div>
            </div>
        `;

        const input = shell.querySelector(`[data-dashboard-datalist-input="${propName}"]`);
        const panel = shell.querySelector(`[data-dashboard-autocomplete-panel="${propName}"]`);
        if (!input || !panel || input.disabled) {
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
            panel.classList.add('hidden');
            panel.innerHTML = '';
            activeIndex = -1;
        };

        const renderPanel = () => {
            const matches = filteredLabels();
            if (matches.length === 0) {
                hidePanel();
                return;
            }

            panel.innerHTML = matches.map((label, index) => `
                <button
                    type="button"
                    class="dashboard-autocomplete-option flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-blue-50 hover:text-blue-700 ${index === activeIndex ? 'bg-blue-50 text-blue-700' : ''}"
                    data-dashboard-option-label="${escapeHtml(label)}"
                >
                    ${escapeHtml(label)}
                </button>
            `).join('');

            panel.classList.remove('hidden');
        };

        const syncValue = () => {
            const component = getDashboardComponent(root);
            if (!component) {
                return;
            }

            const resolvedValue = resolveSelectedValue(select, input.value);
            if (resolvedValue === null) {
                return;
            }

            const currentAppliedValue = normalizeSelectableValue(select.value);
            if (currentAppliedValue === resolvedValue) {
                return;
            }

            applySelectValue(select, resolvedValue);
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
                const resolvedValue = resolveSelectedValue(select, targetLabel);
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

        panel.addEventListener('mousedown', (event) => {
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

    function initDashboardFilterAutocompletes() {
        ensureStyles();

        getDashboardRoots().forEach((root) => {
            root.querySelectorAll('select').forEach((select) => {
                if (getWireModelName(select) === '') {
                    return;
                }

                buildDashboardFilterAutocomplete(root, select);
            });
        });
    }

    function scheduleDashboardFilterAutocompletes(delay = 40) {
        if (refreshTimer) {
            clearTimeout(refreshTimer);
        }

        refreshTimer = window.setTimeout(() => {
            initDashboardFilterAutocompletes();
        }, delay);
    }

    function registerLivewireHooks() {
        if (window.__dashboardFilterAutocompleteHooksRegistered || !window.Livewire?.hook) {
            return;
        }

        window.__dashboardFilterAutocompleteHooksRegistered = true;

        Livewire.hook('request', ({ succeed }) => {
            if (typeof succeed !== 'function') {
                return;
            }

            succeed(() => {
                scheduleDashboardFilterAutocompletes(0);
            });
        });

        try {
            Livewire.hook('morphed', ({ el }) => {
                if (!el) {
                    return;
                }

                const roots = getDashboardRoots();
                const containsDashboardRoot = roots.some((root) => el === root || (typeof el.contains === 'function' && el.contains(root)));
                if (containsDashboardRoot) {
                    scheduleDashboardFilterAutocompletes(0);
                }
            });
        } catch (error) {
            // Ignore if this Livewire hook is unavailable.
        }
    }

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-dashboard-autocomplete-panel]').forEach((panel) => {
            const wrapper = panel.closest('[data-dashboard-autocomplete]');
            if (wrapper && !wrapper.contains(event.target)) {
                panel.classList.add('hidden');
            }
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            scheduleDashboardFilterAutocompletes(0);
            registerLivewireHooks();
        });
    } else {
        scheduleDashboardFilterAutocompletes(0);
        registerLivewireHooks();
    }

    document.addEventListener('livewire:initialized', () => {
        scheduleDashboardFilterAutocompletes(0);
        registerLivewireHooks();
    });
})();

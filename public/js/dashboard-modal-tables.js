(function () {
    'use strict';

    if (window.__dashboardModalTablesLoaded) {
        return;
    }
    window.__dashboardModalTablesLoaded = true;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeCsvValue(value) {
        const normalized = String(value ?? '').replace(/\s+/g, ' ').trim();
        if (normalized.includes('"') || normalized.includes(',') || normalized.includes('\n')) {
            return `"${normalized.replace(/"/g, '""')}"`;
        }

        return normalized;
    }

    function downloadDataUrl(url, fileName) {
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = fileName;
        anchor.style.display = 'none';
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
    }

    function downloadModalTableCsv(container, fileName) {
        const table = container.querySelector('table');
        if (!table) {
            return;
        }

        const headerCells = [...table.querySelectorAll('thead tr:first-child th')];
        const headers = headerCells.map((cell) => String(cell.textContent || '').trim());
        const visibleRows = [...table.querySelectorAll('tbody tr')].filter((row) => !row.classList.contains('hidden'));
        const lines = [headers.map(escapeCsvValue).join(',')];

        visibleRows.forEach((row) => {
            const cells = [...row.querySelectorAll('td')];
            const values = headerCells.map((_, index) => escapeCsvValue(cells[index]?.textContent || ''));
            lines.push(values.join(','));
        });

        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        downloadDataUrl(url, fileName);
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function compareCellValues(left, right) {
        const leftText = String(left ?? '').trim();
        const rightText = String(right ?? '').trim();
        const leftNumber = Number(leftText.replace(/,/g, ''));
        const rightNumber = Number(rightText.replace(/,/g, ''));

        if (leftText !== '' && rightText !== '' && !Number.isNaN(leftNumber) && !Number.isNaN(rightNumber)) {
            return leftNumber - rightNumber;
        }

        return leftText.localeCompare(rightText, undefined, { sensitivity: 'base', numeric: true });
    }

    function shouldSkipDashboardModalTable(table) {
        if (!table) {
            return true;
        }

        if (table.dataset.skipDashboardModalEnhance === '1') {
            return true;
        }

        if (table.closest('[data-skip-dashboard-modal-enhance="1"]')) {
            return true;
        }

        if (table.closest('td, th')) {
            return true;
        }

        return false;
    }

    function enhanceDashboardModalTable(container) {
        if (!container || container.dataset.tableEnhanced === '1') {
            return;
        }

        const table = container.querySelector('table');
        if (shouldSkipDashboardModalTable(table)) {
            return;
        }
        const thead = table?.querySelector('thead');
        const headerRow = thead?.querySelector('tr');
        const tbody = table?.querySelector('tbody');
        if (!table || !thead || !headerRow || !tbody) {
            return;
        }

        container.dataset.tableEnhanced = '1';

        const headerCells = [...headerRow.querySelectorAll('th')];
        const filterRow = document.createElement('tr');
        filterRow.className = 'border-t border-gray-200 bg-white';

        headerCells.forEach((cell, index) => {
            const label = String(cell.textContent || '').trim();
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'group flex w-full items-center gap-1 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-800';
            button.dataset.sortColumn = String(index);
            button.innerHTML = `
                <span>${escapeHtml(label)}</span>
                <span class="text-[10px] text-gray-400 group-hover:text-gray-600" data-sort-indicator></span>
            `;
            cell.textContent = '';
            cell.appendChild(button);

            const th = document.createElement('th');
            th.className = 'px-2 py-2';
            th.innerHTML = `<input
                type="text"
                data-modal-column-filter="${index}"
                placeholder="Filter"
                class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >`;
            filterRow.appendChild(th);
        });

        thead.appendChild(filterRow);

        const tableWrapper = table.closest('.overflow-x-auto');
        const toolbar = document.createElement('div');
        toolbar.className = 'mb-3 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 md:flex-row md:items-center md:justify-between';
        toolbar.innerHTML = `
            <div class="text-xs text-gray-600">
                Filter rows by column, sort by clicking a header, and download the current filtered result.
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    data-modal-clear-filters
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100"
                >
                    Clear filters
                </button>
                <button
                    type="button"
                    data-modal-download-table
                    class="rounded-lg border border-blue-200 bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                >
                    Download CSV
                </button>
            </div>
        `;

        if (tableWrapper) {
            tableWrapper.parentNode.insertBefore(toolbar, tableWrapper);
        } else {
            container.prepend(toolbar);
        }

        const filters = [...filterRow.querySelectorAll('[data-modal-column-filter]')];
        let sortColumn = null;
        let sortDirection = 'asc';

        const getRows = () => [...tbody.querySelectorAll('tr')];

        const updateSortIndicators = () => {
            headerCells.forEach((cell, index) => {
                const indicator = cell.querySelector('[data-sort-indicator]');
                if (!indicator) {
                    return;
                }

                if (sortColumn === index) {
                    indicator.textContent = sortDirection === 'asc' ? '▲' : '▼';
                    indicator.classList.add('text-blue-600');
                } else {
                    indicator.textContent = '↕';
                    indicator.classList.remove('text-blue-600');
                }
            });
        };

        const applyFilters = () => {
            getRows().forEach((row) => {
                const cells = [...row.querySelectorAll('td')];
                const visible = filters.every((input, index) => {
                    const query = String(input.value || '').trim().toLowerCase();
                    if (query === '') {
                        return true;
                    }

                    const value = String(cells[index]?.textContent || '').trim().toLowerCase();

                    return value.includes(query);
                });

                row.classList.toggle('hidden', !visible);
            });
        };

        const applySort = () => {
            if (sortColumn === null) {
                return;
            }

            const rows = getRows();
            const sorted = rows.slice().sort((leftRow, rightRow) => {
                const leftValue = leftRow.querySelectorAll('td')[sortColumn]?.textContent || '';
                const rightValue = rightRow.querySelectorAll('td')[sortColumn]?.textContent || '';
                const comparison = compareCellValues(leftValue, rightValue);

                return sortDirection === 'asc' ? comparison : -comparison;
            });

            sorted.forEach((row) => tbody.appendChild(row));
            applyFilters();
        };

        headerCells.forEach((cell, index) => {
            const button = cell.querySelector('[data-sort-column]');
            if (!button) {
                return;
            }

            button.addEventListener('click', () => {
                if (sortColumn === index) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = index;
                    sortDirection = 'asc';
                }

                updateSortIndicators();
                applySort();
            });
        });

        filters.forEach((input) => {
            input.addEventListener('input', applyFilters);
        });

        toolbar.querySelector('[data-modal-clear-filters]')?.addEventListener('click', () => {
            filters.forEach((input) => {
                input.value = '';
            });
            applyFilters();
        });

        toolbar.querySelector('[data-modal-download-table]')?.addEventListener('click', () => {
            const modalTitle = container.closest('[id$="Modal"]')?.querySelector('h3')?.textContent || 'dashboard-table';
            const fileName = String(modalTitle).trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'dashboard-table';
            downloadModalTableCsv(container, `${fileName}.csv`);
        });

        updateSortIndicators();
    }

    function enhanceDashboardModal(modal) {
        if (!modal) {
            return;
        }

        const tables = [...modal.querySelectorAll('table')].filter((table) => !shouldSkipDashboardModalTable(table));
        const primaryTable = tables.find((table) => table.dataset.dashboardModalPrimaryTable === '1') || tables[0];

        if (!primaryTable) {
            return;
        }

        const container = primaryTable.closest('[data-modal-content]')
            || primaryTable.closest('.overflow-x-auto')
            || primaryTable.parentElement;

        if (container) {
            enhanceDashboardModalTable(container);
        }
    }

    function scheduleEnhanceForModal(modalId) {
        window.setTimeout(() => {
            enhanceDashboardModal(document.getElementById(modalId));
        }, 0);
    }

    function hookOpenModal() {
        if (typeof window.openModal !== 'function' || window.openModal.__dashboardModalHooked) {
            return;
        }

        const original = window.openModal;
        window.openModal = function (modalId) {
            original(modalId);
            scheduleEnhanceForModal(modalId);
        };
        window.openModal.__dashboardModalHooked = true;
    }

    window.enhanceDashboardModalTable = enhanceDashboardModalTable;
    window.enhanceDashboardModal = enhanceDashboardModal;
    window.downloadDashboardModalTableCsv = downloadModalTableCsv;

    document.addEventListener('DOMContentLoaded', () => {
        hookOpenModal();
        window.setTimeout(hookOpenModal, 0);
        window.setTimeout(hookOpenModal, 250);
    });

    document.addEventListener('livewire:initialized', () => {
        hookOpenModal();
        window.setTimeout(hookOpenModal, 0);
    });
})();

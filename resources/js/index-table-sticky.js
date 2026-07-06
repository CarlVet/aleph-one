function resolveStickyColumns(table) {
    if (table.dataset.stickyCols) {
        return table.dataset.stickyCols
            .split(',')
            .map((value) => parseInt(value.trim(), 10))
            .filter((value) => Number.isFinite(value) && value > 0);
    }

    const hasBulk = table.classList.contains('has-bulk-select');

    if (table.classList.contains('sticky-code-at-2')) {
        return [2];
    }

    const tableId = table.id || '';

    if (/tube|culture|box_position|parasite_(samples|human|animal|environment)|experiments/i.test(tableId)) {
        return hasBulk ? [2, 3] : [1, 2];
    }

    return hasBulk ? [2] : [1];
}

function clearStickyColumns(table) {
    table.querySelectorAll('.index-sticky-cell').forEach((cell) => {
        cell.classList.remove('index-sticky-cell', 'index-sticky-cell-last');
        cell.style.removeProperty('left');
        cell.style.removeProperty('min-width');
        cell.style.removeProperty('width');
        cell.style.removeProperty('z-index');
    });
}

function measureColumnWidth(table, columnIndex) {
    const cells = table.querySelectorAll(`thead tr > th:nth-child(${columnIndex}), tbody tr > td:nth-child(${columnIndex})`);

    if (cells.length === 0) {
        return 0;
    }

    let maxWidth = 0;

    cells.forEach((cell) => {
        maxWidth = Math.max(maxWidth, cell.scrollWidth, cell.getBoundingClientRect().width);
    });

    return Math.ceil(maxWidth);
}

export function applyIndexTableSticky() {
    document.querySelectorAll('.index-data-table').forEach((table) => {
        clearStickyColumns(table);

        const columns = resolveStickyColumns(table);

        if (columns.length === 0) {
            return;
        }

        let left = 0;

        columns.forEach((columnIndex, order) => {
            const cells = table.querySelectorAll(`thead tr > th:nth-child(${columnIndex}), tbody tr > td:nth-child(${columnIndex})`);

            if (cells.length === 0) {
                return;
            }

            const width = measureColumnWidth(table, columnIndex);

            if (width <= 0) {
                return;
            }

            const zIndex = 30 - order;
            const isLast = order === columns.length - 1;

            cells.forEach((cell) => {
                cell.classList.add('index-sticky-cell');
                cell.style.left = `${left}px`;
                cell.style.minWidth = `${width}px`;
                cell.style.zIndex = String(zIndex);

                if (isLast) {
                    cell.classList.add('index-sticky-cell-last');
                }
            });

            left += width;
        });
    });
}

let stickyFrame = null;

export function scheduleIndexTableSticky() {
    if (stickyFrame !== null) {
        cancelAnimationFrame(stickyFrame);
    }

    stickyFrame = requestAnimationFrame(() => {
        stickyFrame = null;
        applyIndexTableSticky();
    });
}

export function registerIndexTableSticky() {
    const run = () => {
        applyIndexTableSticky();
        window.setTimeout(applyIndexTableSticky, 120);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }

    window.addEventListener('resize', scheduleIndexTableSticky);

    document.addEventListener('livewire:init', () => {
        run();

        Livewire.hook('morph.updated', () => {
            scheduleIndexTableSticky();
        });

        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => scheduleIndexTableSticky());
        });
    });
}

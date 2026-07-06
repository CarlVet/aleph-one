(function (window) {
  'use strict';

  function escapeLookupHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function datasetKey(key) {
    return String(key || '').replace(/-([a-z])/g, function (_, char) {
      return char.toUpperCase();
    });
  }

  function cellValue(row, key) {
    const value = row && Object.prototype.hasOwnProperty.call(row, key) ? row[key] : '';
    if (Array.isArray(value)) {
      return value.filter(Boolean).join(', ');
    }
    if (value === null || value === undefined || value === '') {
      return '';
    }

    return String(value);
  }

  function setSelectValue(fieldId, value, label) {
    const element = document.getElementById(fieldId);
    if (!element) {
      return;
    }

    const normalizedValue = String(value || '').trim();
    const normalizedLabel = String(label || value || '').trim();
    if (!normalizedValue) {
      return;
    }

    if (element.selectize) {
      const selectize = element.selectize;
      if (!selectize.options[normalizedValue]) {
        selectize.addOption({ value: normalizedValue, text: normalizedLabel || normalizedValue });
      }
      selectize.setValue(normalizedValue, false);
      selectize.trigger('change', normalizedValue);
    } else {
      element.value = normalizedValue;
      element.dispatchEvent(new Event('change', { bubbles: true }));
      element.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  function buildLookupRow(row, config) {
    const value = cellValue(row, config.valueKey);
    const label = cellValue(row, config.labelKey || config.valueKey) || value;
    const dataAttrs = (config.columns || []).map(function (column) {
      const key = column.key;
      const display = cellValue(row, key) || 'N/A';

      return `data-${key.replace(/_/g, '-')}="${escapeLookupHtml(display.toLowerCase())}"`;
    }).join(' ');

    const cells = (config.columns || []).map(function (column) {
      const display = cellValue(row, column.key) || 'N/A';
      let content = column.italic && display !== 'N/A'
        ? `<i>${escapeLookupHtml(display)}</i>`
        : escapeLookupHtml(display);

      if (display !== 'N/A' && column.hrefTemplate) {
        const hrefKey = column.hrefKey || 'id';
        const hrefValue = cellValue(row, hrefKey);
        if (hrefValue) {
          const href = String(column.hrefTemplate).replace(/\{([^}]+)\}/g, function (_, key) {
            return encodeURIComponent(cellValue(row, key) || '');
          });
          content = `<a href="${escapeLookupHtml(href)}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${content}</a>`;
        }
      }

      return `<td class="px-4 py-3 text-gray-700">${content}</td>`;
    }).join('');

    return `
      <tr class="${escapeLookupHtml(config.rowClass)} hover:bg-gray-50" ${dataAttrs}>
        ${cells}
        <td class="px-4 py-3">
          <button type="button"
            class="${escapeLookupHtml(config.selectClass)} inline-flex items-center rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
            data-lookup-value="${escapeLookupHtml(value)}"
            data-lookup-label="${escapeLookupHtml(label)}">
            Select
          </button>
        </td>
      </tr>
    `;
  }

  function populateLookupTableRows(config) {
    const table = document.getElementById(config.tableId);
    if (!table) {
      return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody || tbody.dataset.rowsRendered === '1') {
      return;
    }

    const emptyRow = tbody.querySelector('tr[id$="_empty_state"]');
    const rows = Array.isArray(window[config.rowsKey]) ? window[config.rowsKey] : [];

    rows.forEach(function (row) {
      tbody.insertAdjacentHTML('beforeend', buildLookupRow(row, config));
    });

    if (emptyRow) {
      tbody.appendChild(emptyRow);
    }

    tbody.dataset.rowsRendered = '1';
  }

  function initLookupTable(config) {
    const table = document.getElementById(config.tableId);
    if (!table || table.dataset.lookupInitialized === '1') {
      return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody) {
      return;
    }

    const rows = Array.from(tbody.querySelectorAll(config.rowSelector));
    const filterInputs = Array.from(table.querySelectorAll(config.filterSelector));
    const sortButtons = Array.from(table.querySelectorAll(config.sortSelector));
    const emptyState = document.getElementById(config.emptyStateId);
    let sortKey = '';
    let sortDirection = 'asc';

    function rowValue(row, key) {
      const lookupKey = datasetKey(String(key || '').replace(/_/g, '-'));
      return String((row.dataset && row.dataset[lookupKey]) || '').trim();
    }

    function updateSortIndicators() {
      table.querySelectorAll('[data-sort-indicator]').forEach(function (indicator) {
        const key = indicator.getAttribute('data-sort-indicator') || '';
        if (key !== sortKey) {
          indicator.textContent = '-';
          indicator.classList.remove('text-blue-600');
          indicator.classList.add('text-gray-400');
          return;
        }

        indicator.textContent = sortDirection === 'asc' ? '↑' : '↓';
        indicator.classList.remove('text-gray-400');
        indicator.classList.add('text-blue-600');
      });
    }

    function matchesFilters(row) {
      return filterInputs.every(function (input) {
        const filterKey = input.getAttribute('data-filter-key') || '';
        const filterValue = String(input.value || '').trim().toLowerCase();
        if (!filterValue) {
          return true;
        }

        return rowValue(row, filterKey).toLowerCase().includes(filterValue);
      });
    }

    function compareRows(a, b) {
      if (!sortKey) {
        return 0;
      }

      const aValue = rowValue(a, sortKey);
      const bValue = rowValue(b, sortKey);
      const comparison = aValue.localeCompare(bValue, undefined, {
        numeric: true,
        sensitivity: 'base',
      });

      return sortDirection === 'asc' ? comparison : -comparison;
    }

    function renderLookupTable() {
      const orderedRows = rows.slice().sort(compareRows);
      let visibleCount = 0;

      orderedRows.forEach(function (row) {
        const visible = matchesFilters(row);
        row.classList.toggle('hidden', !visible);
        tbody.appendChild(row);
        if (visible) {
          visibleCount += 1;
        }
      });

      if (emptyState) {
        emptyState.classList.toggle('hidden', visibleCount !== 0);
        tbody.appendChild(emptyState);
      }

      updateSortIndicators();
    }

    filterInputs.forEach(function (input) {
      input.addEventListener('input', renderLookupTable);
    });

    sortButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        const nextSortKey = button.getAttribute('data-sort-key') || '';
        if (!nextSortKey) {
          return;
        }

        if (sortKey === nextSortKey) {
          sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
          sortKey = nextSortKey;
          sortDirection = 'asc';
        }

        renderLookupTable();
      });
    });

    renderLookupTable();
    table.dataset.lookupInitialized = '1';
  }

  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('hidden');
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('hidden');
    }
  }

  function register(config) {
    const prefix = config.prefix;
    const modalId = prefix + '_modal';
    const tableId = prefix + '_table';
    const emptyStateId = prefix + '_empty_state';
    const openBtnId = prefix + '_btn';
    const closeBtnId = prefix + '_close_btn';
    const rowClass = prefix.replace(/_lookup$/, '') + '-lookup-row';
    const selectClass = prefix.replace(/_lookup$/, '') + '-table-select';
    const filterClass = '.' + prefix.replace(/_lookup$/, '') + '-lookup-filter';
    const sortClass = '.' + prefix.replace(/_lookup$/, '') + '-lookup-sort';

    const resolved = Object.assign({}, config, {
      modalId: modalId,
      tableId: tableId,
      emptyStateId: emptyStateId,
      rowClass: rowClass,
      selectClass: selectClass,
      rowSelector: '.' + rowClass,
      filterSelector: filterClass,
      sortSelector: sortClass,
    });

    function ensureTable() {
      populateLookupTableRows(resolved);
      initLookupTable(resolved);
    }

    const openBtn = document.getElementById(openBtnId);
    if (openBtn && openBtn.dataset.lookupBound !== '1') {
      openBtn.addEventListener('click', function () {
        openModal(modalId);
        ensureTable();
      });
      openBtn.dataset.lookupBound = '1';
    }

    const closeBtn = document.getElementById(closeBtnId);
    if (closeBtn && closeBtn.dataset.lookupBound !== '1') {
      closeBtn.addEventListener('click', function () {
        closeModal(modalId);
      });
      closeBtn.dataset.lookupBound = '1';
    }

    if (!registeredSelectHandlers.has(prefix)) {
      document.addEventListener('click', function (event) {
        const button = event.target.closest('.' + selectClass);
        if (!button) {
          return;
        }

        const value = button.getAttribute('data-lookup-value') || '';
        const label = button.getAttribute('data-lookup-label') || value;
        setSelectValue(resolved.selectFieldId, value, label);
        closeModal(modalId);

        if (typeof resolved.onSelect === 'function') {
          resolved.onSelect(value, label, button);
        }
      });
      registeredSelectHandlers.add(prefix);
    }

    return resolved;
  }

  const registeredSelectHandlers = new Set();

  window.AlephLookup = {
    escapeLookupHtml: escapeLookupHtml,
    setSelectValue: setSelectValue,
    register: register,
    openModal: openModal,
    closeModal: closeModal,
  };
})(window);

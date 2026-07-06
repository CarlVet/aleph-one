$(document).ready(function () {
  function initStaticSelectize(selectId, placeholder, allowCreate = false) {
    const $select = $(`#${selectId}`);
    if (!$select.length) return;
    if ($select[0] && $select[0].selectize) return;

    $select.selectize({
      placeholder,
      create: allowCreate,
      dropdownParent: 'body',
      openOnFocus: true,
    });
  }

  function initAjaxSelectize(selectId, placeholder, searchUrl) {
    $(`#${selectId}`).selectize({
      placeholder,
      create: false,
      maxOptions: 20,
      dropdownParent: 'body',
      openOnFocus: true,
      valueField: 'value',
      labelField: 'text',
      searchField: ['text'],
      preload: false,
      load: function (query, callback) {
        if (!query.length) return callback();

        $.ajax({
          url: searchUrl,
          type: 'GET',
          dataType: 'json',
          data: { q: query },
          error: function () {
            callback();
          },
          success: function (res) {
            callback(res);
          },
        });
      },
    });
  }

  initAjaxSelectize(
    'nucleic_tube_id',
    'Enter nucleic tube code',
    '/samples/nucleic/sequences/create/tubes/nucleic/search'
  );

  initStaticSelectize('method', 'Select or type sequencing method', true);
  initStaticSelectize('instrument', 'Select or type sequencing instrument', true);
  initStaticSelectize('laboratories_id', 'Select sequencing location');
  initStaticSelectize('people_id', 'Select sequenced by');
  initStaticSelectize('people_id_locked', 'Sequenced by');
  initStaticSelectize('sub_project_id', 'Select sub-project');

  $('#sequence_lab_form_btn').on('click', function () {
    $('#sequence_lab_form_modal').removeClass('hidden');
  });

  $('#sequence_lab_form_close_btn').on('click', function () {
    $('#sequence_lab_form_modal').addClass('hidden');
  });

  setupTubeSelector({
    selectId: 'nucleic_tube_id',
    modalId: 'nucleic_tubes_modal',
    openBtnId: 'nucleic_tubes_btn',
    closeBtnId: 'nucleic_tubes_close_btn',
    confirmBtnId: 'confirm_na_tube_selection',
    checkboxClass: 'select-na-tube',
    masterCheckboxId: 'select_all_na_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/nucleic/sequences/create/tubes/nucleic',
  });
});

function resolveModalUrl(url, baseUrl) {
  try {
    return new URL(url, new URL(baseUrl, window.location.origin));
  } catch {
    return null;
  }
}

function buildModalUrlWithFilters(url, filters, baseUrl, options) {
  const u = resolveModalUrl(url, baseUrl);
  if (!u) {
    return url;
  }

  const preservePage = Boolean(options && options.preservePage);
  const perPage = options && options.perPage ? Number(options.perPage) : null;
  const sortCol = options && (options.sortCol !== undefined) ? options.sortCol : null;
  const sortDir = options && options.sortDir ? String(options.sortDir) : null;

  for (const key of Array.from(u.searchParams.keys())) {
    if (!preservePage && (key === 'page' || key.endsWith('_page'))) {
      u.searchParams.delete(key);
    }
    if (key.startsWith('filters[')) {
      u.searchParams.delete(key);
    }
  }

  Object.keys(filters || {}).forEach((k) => {
    const idx = Number(k);
    const v = (filters[idx] || '').toString().trim();
    if (v) {
      u.searchParams.set(`filters[${idx}]`, v);
    }
  });

  if (perPage && !Number.isNaN(perPage)) {
    u.searchParams.set('perPage', String(perPage));
  } else {
    u.searchParams.delete('perPage');
  }

  if (sortCol !== null && sortCol !== undefined && String(sortCol).trim() !== '') {
    u.searchParams.set('sort_col', String(sortCol));
    u.searchParams.set('sort_dir', (sortDir === 'desc') ? 'desc' : 'asc');
  } else {
    u.searchParams.delete('sort_col');
    u.searchParams.delete('sort_dir');
  }

  return u.pathname + (u.search ? u.search : '');
}

function ensureColumnFilters(table, initialFilters, onChange) {
  if (!table) return;
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) return;

  const existing = thead.querySelector('tr[data-column-filters=\"1\"]');
  if (existing) {
    existing.remove();
  }

  const headerRow = thead.querySelector('tr');
  if (!headerRow) return;

  const filterRow = document.createElement('tr');
  filterRow.setAttribute('data-column-filters', '1');

  const ths = Array.from(headerRow.querySelectorAll('th'));
  ths.forEach((th, index) => {
    const filterTh = document.createElement('th');
    filterTh.className = th.className;
    filterTh.classList.add('bg-gray-50');
    filterTh.style.verticalAlign = 'top';

    const hasCheckbox = th.querySelector('input[type=\"checkbox\"]');
    if (hasCheckbox || index === 0) {
      filterTh.innerHTML = '&nbsp;';
      filterRow.appendChild(filterTh);
      return;
    }

    const input = document.createElement('input');
    input.type = 'text';
    input.inputMode = 'search';
    input.placeholder = 'Filter…';
    input.setAttribute('data-filter-col', String(index));
    input.value = (initialFilters && initialFilters[index]) ? String(initialFilters[index]) : '';
    input.className =
      'w-full rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
    input.addEventListener('input', (e) => {
      if (typeof onChange === 'function') {
        onChange(index, e.target.value, e.target);
      }
    });

    filterTh.appendChild(input);
    filterRow.appendChild(filterTh);
  });

  thead.appendChild(filterRow);
}

function ensureSortableHeaders(table) {
  if (!table) return;
  const thead = table.querySelector('thead');
  if (!thead) return;
  const headerRow = thead.querySelector('tr');
  if (!headerRow) return;

  const ths = Array.from(headerRow.querySelectorAll('th'));
  ths.forEach((th) => {
    if (th.querySelector('input[type="checkbox"]')) {
      return;
    }

    th.classList.add('cursor-pointer', 'select-none');
    if (!th.getAttribute('title')) {
      th.setAttribute('title', 'Click to sort');
    }

    const hasIcon = Boolean(th.querySelector('svg'));
    if (!hasIcon) {
      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      icon.setAttribute('viewBox', '0 0 24 24');
      icon.setAttribute('fill', 'none');
      icon.setAttribute('stroke', 'currentColor');
      icon.setAttribute('data-sort-icon', '1');
      icon.classList.add('w-4', 'h-4', 'ml-1', 'text-gray-400');
      icon.innerHTML =
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>';

      const wrapper = th.querySelector('div') || th;
      wrapper.appendChild(icon);
    }
  });
}

function setupTubeSelector({
  selectId,
  modalId,
  openBtnId,
  closeBtnId,
  confirmBtnId,
  checkboxClass,
  masterCheckboxId,
  countElementId,
  fetchUrl,
}) {
  const $select = $(`#${selectId}`);
  const selectizeControl = $select[0].selectize;
  const countSpanId = `${selectId}_count`;
  const $modal = $(`#${modalId}`);
  const $modalContent = $modal.find('[data-modal-content]').first();

  const baseUrl = fetchUrl;
  let currentUrl = fetchUrl;
  let currentFilters = {};
  let filterDebounce = null;
  let perPage = 50;
  let sortCol = null;
  let sortDir = 'asc';
  let lastFilterFocus = null;

  let pendingSelectedIds = new Set();
  let pendingLabelsById = {};

  function updateTubeCount() {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
  }

  function updateModalCheckboxCount() {
    const checkedCount = pendingSelectedIds.size;
    const $modalCountSpan = $(`#${modalId} #${countElementId}`);
    if ($modalCountSpan.length > 0) {
      $modalCountSpan.text(checkedCount);
    }
  }

  function syncPendingFromSelectize() {
    pendingSelectedIds = new Set(selectizeControl.items.map(String));
    pendingLabelsById = {};
    selectizeControl.items.forEach(function (id) {
      const key = String(id);
      const opt = selectizeControl.options[key];
      if (opt && opt.text) {
        pendingLabelsById[key] = String(opt.text);
      }
    });
  }

  function syncCheckboxesWithPending() {
    const selectedItems = pendingSelectedIds;

    $(`#${modalId} .${checkboxClass}`).each(function () {
      const checkbox = $(this);
      const id = String(checkbox.val());
      checkbox.prop('checked', selectedItems.has(id));
    });

    const allChecked =
      $(`#${modalId} .${checkboxClass}`).length ===
      $(`#${modalId} .${checkboxClass}:checked`).length;
    $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);
    updateModalCheckboxCount();
  }

  function ensurePageSizeControl() {
    const root = $modalContent.get(0);
    if (!root) return;
    const table = root.querySelector('table');
    if (!table) return;

    const existing = root.querySelector('[data-page-size-control="1"]');
    if (existing) {
      const select = existing.querySelector('select');
      if (select) {
        select.value = String(perPage);
      }
      return;
    }

    const bar = document.createElement('div');
    bar.setAttribute('data-page-size-control', '1');
    bar.className = 'flex items-center justify-end gap-2 py-2';

    const label = document.createElement('span');
    label.className = 'text-xs text-gray-600';
    label.textContent = 'Rows per page:';

    const select = document.createElement('select');
    select.className = 'rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500';
    [10, 50, 100, 200].forEach((n) => {
      const opt = document.createElement('option');
      opt.value = String(n);
      opt.textContent = String(n);
      if (n === perPage) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });
    select.addEventListener('change', function () {
      perPage = Number(this.value) || 50;
      loadModalContent(baseUrl, { preservePage: false });
    });

    bar.appendChild(label);
    bar.appendChild(select);
    table.parentNode.insertBefore(bar, table);
  }

  function loadModalContent(url, options) {
    const silent = Boolean(options && options.silent);
    if (!silent) {
      $modalContent.html('<div class=\"text-sm text-gray-500\">Loading…</div>');
    }
    const preservePage = Boolean(options && options.preservePage);

    const resolved = resolveModalUrl(url, currentUrl || baseUrl);
    const normalizedUrl = resolved ? resolved.pathname + (resolved.search || '') : url;
    const requestUrl = buildModalUrlWithFilters(normalizedUrl, currentFilters, baseUrl, {
      preservePage,
      perPage,
      sortCol,
      sortDir,
    });
    currentUrl = requestUrl;

    $.get(requestUrl)
      .done(function (html) {
        $modalContent.html(html);

        const table = $modalContent.get(0).querySelector('table');
        if (table) {
          ensurePageSizeControl();
          ensureSortableHeaders(table);
          ensureColumnFilters(table, currentFilters, function (colIndex, value, inputEl) {
            lastFilterFocus = {
              colIndex,
              caretStart: inputEl && typeof inputEl.selectionStart === 'number' ? inputEl.selectionStart : null,
              caretEnd: inputEl && typeof inputEl.selectionEnd === 'number' ? inputEl.selectionEnd : null,
            };
            const v = (value || '').toString();
            if (v.trim() === '') {
              delete currentFilters[colIndex];
            } else {
              currentFilters[colIndex] = v;
            }

            if (filterDebounce) {
              clearTimeout(filterDebounce);
            }
            filterDebounce = setTimeout(function () {
              loadModalContent(baseUrl, { preservePage: false, silent: true });
            }, 500);
          });

          if (lastFilterFocus && Number.isInteger(lastFilterFocus.colIndex)) {
            const selector = `input[data-filter-col="${lastFilterFocus.colIndex}"]`;
            const focusInput = table.querySelector(selector);
            if (focusInput) {
              focusInput.focus();
              const valueLength = focusInput.value.length;
              const caretStart = lastFilterFocus.caretStart;
              const caretEnd = lastFilterFocus.caretEnd;
              if (typeof caretStart === 'number' && typeof caretEnd === 'number') {
                const safeStart = Math.min(Math.max(0, caretStart), valueLength);
                const safeEnd = Math.min(Math.max(0, caretEnd), valueLength);
                focusInput.setSelectionRange(safeStart, safeEnd);
              } else {
                focusInput.setSelectionRange(valueLength, valueLength);
              }
            }
          }
        }

        syncCheckboxesWithPending();
      })
      .fail(function () {
        $modalContent.html('<div class=\"text-sm text-red-600\">Failed to load data.</div>');
      });
  }

  $(`#${openBtnId}`).on('click', function () {
    $(`#${modalId}`).removeClass('hidden');
    syncPendingFromSelectize();
    loadModalContent(fetchUrl, { preservePage: false });
  });

  $(`#${closeBtnId}`).on('click', function () {
    $(`#${modalId}`).addClass('hidden');
  });

  $(document).on('click', `#${modalId} button[id$=\"_cancel_btn\"]`, function () {
    $(`#${modalId}`).addClass('hidden');
  });

  $(document).on('click', `#${modalId} #${confirmBtnId}`, function () {
    selectizeControl.clear(true);

    Array.from(pendingSelectedIds).forEach(function (id) {
      const label = pendingLabelsById[id] || (selectizeControl.options[id] ? selectizeControl.options[id].text : id);
      if (!selectizeControl.options[id]) {
        selectizeControl.addOption({ value: id, text: label });
      }
      selectizeControl.addItem(id);
    });

    updateTubeCount();
    $(`#${modalId}`).addClass('hidden');
  });

  selectizeControl.on('item_remove', updateTubeCount);
  selectizeControl.on('item_add', updateTubeCount);

  $(document).on('change', `#${modalId} #${masterCheckboxId}`, function () {
    const isChecked = $(this).is(':checked');
    $(`#${modalId} .${checkboxClass}`).each(function () {
      const checkbox = $(this);
      const id = String(checkbox.val());
      checkbox.prop('checked', isChecked);
      if (isChecked) {
        pendingSelectedIds.add(id);
        if (!pendingLabelsById[id]) {
          pendingLabelsById[id] = checkbox.closest('tr').children().eq(1).text().trim();
        }
      } else {
        pendingSelectedIds.delete(id);
      }
    });
    updateModalCheckboxCount();
  });

  $(document).on('change', `#${modalId} .${checkboxClass}`, function () {
    const allChecked =
      $(`#${modalId} .${checkboxClass}`).length ===
      $(`#${modalId} .${checkboxClass}:checked`).length;
    $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);
    const checkbox = $(this);
    const id = String(checkbox.val());
    if (checkbox.is(':checked')) {
      pendingSelectedIds.add(id);
      if (!pendingLabelsById[id]) {
        pendingLabelsById[id] = checkbox.closest('tr').children().eq(1).text().trim();
      }
    } else {
      pendingSelectedIds.delete(id);
    }
    updateModalCheckboxCount();
  });

  // Server-side sorting inside the modal (click column headers)
  $(document).on('click', `#${modalId} table thead tr:first-child th`, function () {
    const th = $(this);
    if (th.find('input[type="checkbox"]').length) {
      return;
    }

    const colIndex = th.index();
    if (colIndex <= 0) {
      return;
    }

    if (sortCol === colIndex) {
      sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      sortCol = colIndex;
      sortDir = 'asc';
    }

    loadModalContent(baseUrl, { preservePage: false });
  });

  $(document).on('click', `#${modalId} nav a[href]`, function (e) {
    e.preventDefault();
    const url = $(this).attr('href');
    if (!url) {
      return;
    }
    loadModalContent(url, { preservePage: true });
  });

  updateTubeCount();
}

// Get the success and error message elements from the DOM
const successMessageElement = document.getElementById('successMessage');
const errorMessageElement = document.getElementById('errorMessage');

// Show success message if it exists
if (successMessageElement) {
  Swal.fire({
    icon: 'success',
    title: 'Success',
    text: successMessageElement.textContent,
  });
}

// Show error message if it exists
if (errorMessageElement) {
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: errorMessageElement.textContent,
  });
}

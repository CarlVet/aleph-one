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

  // Reset pagination when filters change (but NOT when user clicks pagination).
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

  const existing = thead.querySelector('tr[data-column-filters="1"]');
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

    const hasCheckbox = th.querySelector('input[type="checkbox"]');
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
    input.value = initialFilters && initialFilters[index] ? String(initialFilters[index]) : '';
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

$(document).ready(function () {
  function initAjaxSelectize(selectId, placeholder, searchUrl) {
    const $el = $(`#${selectId}`).selectize({
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

    return $el.length ? $el[0].selectize : null;
  }

  const humansSelectize = initAjaxSelectize(
    'humans_id',
    'Search and select patient code',
    '/samples/humans/create/humans/search'
  );

  $('#human_site').selectize({
    placeholder: 'Search and select sampling site',
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  $('#sampling_purpose').selectize({
    placeholder: 'Search or enter sampling purpose',
    create: true,
    dropdownParent: 'body',
  });

  $('#scientist').selectize({
    placeholder: 'Select scientist',
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  // Human sample types (with "new sample types" category subtable support)

  if ($('#human_sample_type').length) {
    const sampleTypeSelectize = $('#human_sample_type').selectize({
      placeholder: 'Search or enter sample types',
      create: true,
      dropdownParent: 'body',
    });

    const selectize = sampleTypeSelectize[0].selectize;
    const existingTypes = new Set(Object.keys(selectize.options).map(String));
    const newTypeKeyByValue = new Map();
    let newTypeKeySeq = 1;

    const $newSection = $('#new_sample_types_section');
    const $newContainer = $('#new_sample_types_container');

    const ensureNewTypeRow = (value) => {
      const v = String(value || '').trim();
      if (!v) return;

      let key = newTypeKeyByValue.get(v);
      if (!key) {
        key = String(newTypeKeySeq++);
        newTypeKeyByValue.set(v, key);
      }

      if ($newContainer.find(`[data-new-sample-type-key="${key}"]`).length) {
        return;
      }

      const safeLabel = v.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const html = `
        <div data-new-sample-type-key="${key}" class="rounded-lg border border-gray-200 bg-white p-3">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-gray-900 break-words">${safeLabel}</div>
              <div class="text-xs text-gray-600 mt-0.5">Is this sample type host derived?</div>
            </div>
            <button type="button" class="remove-new-sample-type inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 text-gray-600" title="Remove">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <input type="hidden" name="new_sample_types[${key}][name]" value="${v.replace(/"/g, '&quot;')}">

          <div class="mt-3 flex flex-col sm:flex-row gap-3">
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
              <input required type="radio" name="new_sample_types[${key}][category]" value="host_derived" class="text-blue-600 focus:ring-blue-500">
              <span>Host derived</span>
            </label>
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer">
              <input type="radio" name="new_sample_types[${key}][category]" value="non_host_derived" class="text-blue-600 focus:ring-blue-500">
              <span>Non-host derived</span>
            </label>
          </div>
        </div>
      `;

      $newContainer.append(html);
    };

    const refreshNewTypesSectionVisibility = () => {
      const hasRows = $newContainer.children().length > 0;
      $newSection.toggleClass('hidden', !hasRows);
    };

    selectize.on('item_add', (value) => {
      const v = String(value || '').trim();
      if (!v) return;
      if (!existingTypes.has(v)) {
        ensureNewTypeRow(v);
        refreshNewTypesSectionVisibility();
      }
    });

    selectize.on('item_remove', (value) => {
      const v = String(value || '').trim();
      if (!v) return;

      const key = newTypeKeyByValue.get(v);
      if (key) {
        $newContainer.find(`[data-new-sample-type-key="${key}"]`).remove();
      }
      refreshNewTypesSectionVisibility();
    });

    $newContainer.on('click', '.remove-new-sample-type', function () {
      const $row = $(this).closest('[data-new-sample-type-key]');
      const key = $row.attr('data-new-sample-type-key');
      const name = $row.find(`input[name="new_sample_types[${key}][name]"]`).val();

      $row.remove();
      refreshNewTypesSectionVisibility();

      if (name) {
        try {
          selectize.removeItem(String(name), true);
        } catch (_) {}
      }
    });

    // On page load with old input (validation error), rebuild new sample type rows if needed.
    (selectize.items || []).forEach((v) => {
      const value = String(v || '').trim();
      if (value && !existingTypes.has(value)) {
        ensureNewTypeRow(value);
      }
    });
    refreshNewTypesSectionVisibility();
  }

  $('#storage_state').selectize({
    placeholder: 'Search or enter storage state',
    create: true,
    dropdownParent: 'body',
  });

  $('#human_location').selectize({
    placeholder: 'Search storage location',
    create: false,
    dropdownParent: 'body',
  });

  if ($('#sub_project_id').length) {
    $('#sub_project_id').selectize({
      placeholder: 'Select sub-project',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }

  document.getElementById('humans_form_btn')?.addEventListener('click', function () {
    document.getElementById('humans_form_modal')?.classList.remove('hidden');
  });

  document.getElementById('humans_form_close_btn')?.addEventListener('click', function () {
    document.getElementById('humans_form_modal')?.classList.add('hidden');
  });

  document.getElementById('human_site_form_btn')?.addEventListener('click', function () {
    document.getElementById('human_site_form_modal')?.classList.remove('hidden');
  });

  document.getElementById('human_site_form_close_btn')?.addEventListener('click', function () {
    document.getElementById('human_site_form_modal')?.classList.add('hidden');
  });

  document.getElementById('location_form_btn')?.addEventListener('click', function () {
    document.getElementById('location_form_modal')?.classList.remove('hidden');
  });

  document.getElementById('location_form_close_btn')?.addEventListener('click', function () {
    document.getElementById('location_form_modal')?.classList.add('hidden');
  });

  function updateSelectedCount(selectizeControl, countSpanId) {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
  }

  function setupEntitySelector({
    selectizeControl,
    selectId,
    modalId,
    openBtnId,
    closeBtnId,
    confirmBtnId,
    checkboxClass,
    masterCheckboxId,
    fetchUrl,
  }) {
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

    function updateModalCheckboxCount() {
      $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
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
        $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
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
      select.className = 'rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-pink-500 focus:outline-none focus:ring-1 focus:ring-pink-500';
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
        $modalContent.html('<div class="text-sm text-gray-500">Loading…</div>');
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
          $modalContent.html('<div class="text-sm text-red-600">Failed to load data.</div>');
        });
    }

    $(`#${openBtnId}`).on('click', function () {
      $(`#${modalId}`).show();
      syncPendingFromSelectize();
      loadModalContent(fetchUrl, { preservePage: false });
    });

    $(`#${closeBtnId}`).on('click', function () {
      $(`#${modalId}`).hide();
    });

    // Cancel buttons inside dynamically loaded content
    $(document).on('click', `#${modalId} [id$="_cancel_btn"]`, function () {
      $(`#${modalId}`).hide();
    });

    $(document).on('click', `#${modalId} #${confirmBtnId}`, function (e) {
      e.preventDefault();
      e.stopPropagation();

      selectizeControl.clear(true);

      Array.from(pendingSelectedIds).forEach(function (id) {
        const code = pendingLabelsById[id] || (selectizeControl.options[id] ? selectizeControl.options[id].text : id);

        if (!selectizeControl.options[id]) {
          selectizeControl.addOption({ value: id, text: code });
        }
        selectizeControl.addItem(id);
      });

      updateSelectedCount(selectizeControl, 'humans_count');
      $(`#${modalId}`).hide();
    });

    selectizeControl.on('item_remove', function () {
      updateSelectedCount(selectizeControl, 'humans_count');
    });
    selectizeControl.on('item_add', function () {
      updateSelectedCount(selectizeControl, 'humans_count');
    });

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
        $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
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

    // AJAX pagination inside the modal (no full page reload)
    $(document).on('click', `#${modalId} nav a[href]`, function (e) {
      e.preventDefault();
      const url = $(this).attr('href');
      if (!url) return;

      loadModalContent(url, { preservePage: true });
    });

    updateSelectedCount(selectizeControl, 'humans_count');
  }

  if (humansSelectize && $('#humans_modal').length) {
    setupEntitySelector({
      selectizeControl: humansSelectize,
      selectId: 'humans_id',
      modalId: 'humans_modal',
      openBtnId: 'humans_btn',
      closeBtnId: 'humans_close_btn',
      confirmBtnId: 'confirm_human_selection',
      checkboxClass: 'select-humans',
      masterCheckboxId: 'select_all_humans',
      fetchUrl: '/samples/humans/create/humans',
    });
  }

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'locations_lookup',
      rowsKey: 'locationLookupRows',
      selectFieldId: 'human_location',
      valueKey: 'id',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'type' }, { key: 'room' }, { key: 'barcode' },
        { key: 'laboratory' }, { key: 'country' },
      ],
    });

    window.AlephLookup.register({
      prefix: 'sampling_sites_lookup',
      rowsKey: 'samplingSiteLookupRows',
      selectFieldId: 'human_site',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'site_type' }, { key: 'country' }, { key: 'region' },
        { key: 'city' }, { key: 'organization' }, { key: 'latitude' }, { key: 'longitude' },
      ],
    });
  }

  const successMessageElement = document.getElementById('humanSampleSuccessMessage');
  const errorMessageElement = document.getElementById('humanSampleErrorMessage');

  if (successMessageElement && typeof Swal !== 'undefined') {
    Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent });
  }
  if (errorMessageElement && typeof Swal !== 'undefined') {
    Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent });
  }
});


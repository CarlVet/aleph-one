$(document).ready(function () {

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'laboratories_lookup',
      rowsKey: 'laboratoryLookupRows',
      selectFieldId: 'lab',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'lab_type' }, { key: 'country' }, { key: 'city' },
        { key: 'address' }, { key: 'latitude' }, { key: 'longitude' },
      ],
    });
  }

  // Get initial value from checked radio button
  const cultureStepValue = $('input[name="culture_step"]:checked').val();
  toggleCultureStep(cultureStepValue);

  // Listen for changes on any radio button with name culture_step
  $('input[name="culture_step"]').on('change', function () {
    toggleCultureStep($(this).val());
    setTimeout(function () {
      window.alephRefreshAllTubeBadgeDisplays?.();
    }, 0);
  });

  var $modelSelect = $('#model').selectize({
    placeholder: "Select sample origin",
    create: false,
    dropdownParent: 'body',
    onChange: function (value) {
      toggleFields(value);
      setTimeout(function () {
        window.alephRefreshAllTubeBadgeDisplays?.();
      }, 0);
    }
  });

  function initAjaxSelectize(selectId, placeholder, searchUrl, onChange) {
    $(`#${selectId}`).selectize({
      placeholder,
      create: false,
      maxOptions: 20,
      dropdownParent: 'body',
      openOnFocus: true,
      valueField: 'value',
      labelField: 'text',
      searchField: ['text', 'code', 'alias_code'],
      preload: false,
      onInitialize: function () {
        window.alephConfigureTubeBadgeSelectize?.(this.$input[0]);
      },
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
            const normalized = Array.isArray(res)
              ? res.map((item) => {
                  const normalizedItem = window.alephNormalizeTubeOption ? window.alephNormalizeTubeOption(item) : item;
                  if (window.alephGetTubeDropdownLabel) {
                    normalizedItem.text = window.alephGetTubeDropdownLabel(normalizedItem);
                  }
                  return normalizedItem;
                })
              : res;
            callback(normalized);
          },
        });
      },
      onChange,
    });
  }

  initAjaxSelectize('animal_tube_id', 'Enter animal tube code', '/samples/cultures/create/tubes/animal/search');
  initAjaxSelectize('parasite_tube_id', 'Enter parasite tube code', '/samples/cultures/create/tubes/parasite/search');
  initAjaxSelectize('human_tube_id', 'Enter human tube code', '/samples/cultures/create/tubes/human/search');
  initAjaxSelectize('environment_tube_id', 'Enter environment tube code', '/samples/cultures/create/tubes/environment/search');
  initAjaxSelectize('pool_tube_id', 'Enter pool tube code', '/samples/cultures/create/tubes/pool/search');
  initAjaxSelectize('culture_id', 'Enter culture code', '/samples/cultures/create/cultures/search', updateTubeAssignments);
  initAjaxSelectize('nucleic_tube_id', 'Enter nucleic tube code', '/samples/nucleic/create/tubes/nucleic/search');

  $('#culture_type').selectize({
    placeholder: "Search and select culture type",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#culture_medium').selectize({
    placeholder: "Select or enter new medium",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#culture_athmosphere').selectize({
    placeholder: "Select or enter new athmospherical conditions",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#lab').selectize({
    placeholder: "Search and select laboratory",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  $('#culture_lab_form_btn').on('click', function () {
    $('#culture_lab_form_modal').removeClass('hidden');
  });

  $('#culture_lab_form_close_btn').on('click', function () {
    $('#culture_lab_form_modal').addClass('hidden');
  });

  $('#auto_alias_from_parent').on('change', function () {
    updateTubeAssignments();
  });

  $('#scientist').selectize({
    placeholder: "Search and select scientist",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  if ($('#sub_project_id').length) {
    $('#sub_project_id').selectize({
      placeholder: 'Select sub-project',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }

  // Tables are server-paginated and loaded on-demand (no DataTables).

  setupTubeSelector({
    selectId: 'animal_tube_id',
    modalId: 'animal_tubes_modal',
    openBtnId: 'animal_tubes_btn',
    closeBtnId: 'animal_tubes_close_btn',
    confirmBtnId: 'confirm_tube_selection',
    checkboxClass: 'select-tube',
    masterCheckboxId: 'select_all_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/tubes/animal'
  });

  setupTubeSelector({
    selectId: 'parasite_tube_id',
    modalId: 'parasite_tubes_modal',
    openBtnId: 'parasite_tubes_btn',
    closeBtnId: 'parasite_tubes_close_btn',
    confirmBtnId: 'confirm_parasite_tube_selection',
    checkboxClass: 'select-parasite-tube',
    masterCheckboxId: 'select_all_parasite_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/tubes/parasite'
  });

  setupTubeSelector({
    selectId: 'human_tube_id',
    modalId: 'human_tubes_modal',
    openBtnId: 'human_tubes_btn',
    closeBtnId: 'human_tubes_close_btn',
    confirmBtnId: 'confirm_human_tube_selection',
    checkboxClass: 'select-human-tube',
    masterCheckboxId: 'select_all_human_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/tubes/human'
  });

  setupTubeSelector({
    selectId: 'environment_tube_id',
    modalId: 'environment_tubes_modal',
    openBtnId: 'environment_tubes_btn',
    closeBtnId: 'environment_tubes_close_btn',
    confirmBtnId: 'confirm_environment_tube_selection',
    checkboxClass: 'select-environment-tube',
    masterCheckboxId: 'select_all_environment_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/tubes/environment'
  });

  setupTubeSelector({
    selectId: 'pool_tube_id',
    modalId: 'pool_tubes_modal',
    openBtnId: 'pool_tubes_btn',
    closeBtnId: 'pool_tubes_close_btn',
    confirmBtnId: 'confirm_pool_tube_selection',
    checkboxClass: 'select-pool-tube',
    masterCheckboxId: 'select_all_pool_tubes',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/tubes/pool'
  });

  setupTubeSelector({
    selectId: 'culture_id',
    modalId: 'cultures_modal',
    openBtnId: 'cultures_btn',
    closeBtnId: 'cultures_close_btn',
    confirmBtnId: 'confirm_culture_selection',
    checkboxClass: 'select-culture',
    masterCheckboxId: 'select_all_cultures',
    countElementId: 'selected_count',
    fetchUrl: '/samples/cultures/create/cultures'
  });

  // Get the Selectize instance correctly
  var selectizeInstance = $modelSelect[0].selectize;
  var initialValue = selectizeInstance.getValue(); // Get the initial value
  toggleFields(initialValue); // Initialize fields

  // Add event listeners for tube selection changes
  $('#animal_tube_id, #parasite_tube_id, #human_tube_id, #environment_tube_id, #pool_tube_id, #culture_id').on('change', function() {
    updateTubeAssignments();
  });

  // Initial update
  updateTubeAssignments();
  window.alephRefreshAllTubeBadgeDisplays?.();

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
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
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
  aliasColumnIndex = 2
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
  let pendingAliasesById = {};
  
  function updateTubeCount() {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
    window.alephRefreshTubeBadgeDisplay?.(selectId);
  }

  // Update count inside modal (checkboxes checked)
  function updateModalCheckboxCount() {
    const checkedCount = pendingSelectedIds.size;
    // Update the count element in the modal
    const $modalCountSpan = $(`#${modalId} #${countElementId}`);
    if ($modalCountSpan.length > 0) {
      $modalCountSpan.text(checkedCount);
    }
  }

  function syncPendingFromSelectize() {
    pendingSelectedIds = new Set(selectizeControl.items.map(String));
    pendingLabelsById = {};
    pendingAliasesById = {};
    selectizeControl.items.forEach(function (id) {
      const key = String(id);
      const opt = selectizeControl.options[key];
      if (opt && opt.text) {
        pendingLabelsById[key] = String(opt.code || opt.text);
        if (opt.alias_code) {
          pendingAliasesById[key] = String(opt.alias_code);
        }
      }
    });
  }

  function getAliasFromRow(checkbox) {
    const raw = checkbox.closest('tr').children().eq(aliasColumnIndex).text().trim();
    if (!raw || raw.toUpperCase() === 'N/A') {
      return '';
    }

    return raw;
  }

  function getTubeDisplayLabel(code, alias) {
    const normalizedCode = String(code || '').trim();
    const normalizedAlias = String(alias || '').trim();

    if (window.alephGetTubeBadgeDisplayMode?.() === 'alias' && normalizedAlias !== '') {
      return normalizedAlias;
    }

    return normalizedCode;
  }

  function syncCheckboxesWithPending() {
    const selectedItems = pendingSelectedIds;

    $(`#${modalId} .${checkboxClass}`).each(function () {
      const checkbox = $(this);
      const id = String(checkbox.val());
      checkbox.prop('checked', selectedItems.has(id));
    });

    // Update master checkbox state
    const allChecked = $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
    $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);

    // Update modal checkbox count
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

  // Show modal (load content on demand)
  $(`#${openBtnId}`).on('click', function () {
    $(`#${modalId}`).removeClass('hidden');
    syncPendingFromSelectize();
    loadModalContent(fetchUrl, { preservePage: false });
  });

  // Close modal
  $(`#${closeBtnId}`).on('click', function () {
    $(`#${modalId}`).addClass('hidden');
  });



  // Cancel buttons inside dynamically loaded content
  $(document).on('click', `#${modalId} button[id$=\"_cancel_btn\"]`, function () {
    $(`#${modalId}`).addClass('hidden');
  });

  // Modal's built-in close button (× in header)
  $(`#${closeBtnId}`).on('click', function() {
    $(`#${modalId}`).addClass('hidden');
  });

  // Confirm selection (sync Selectize with checked checkboxes)
  $(document).on('click', `#${modalId} #${confirmBtnId}`, function () {
    selectizeControl.clear(true);

    Array.from(pendingSelectedIds).forEach(function (id) {
      const label = pendingLabelsById[id] || (selectizeControl.options[id] ? (selectizeControl.options[id].code || selectizeControl.options[id].text) : id);
      const alias = pendingAliasesById[id] || (selectizeControl.options[id] ? selectizeControl.options[id].alias_code : '');
      const displayLabel = getTubeDisplayLabel(label, alias);
      if (!selectizeControl.options[id]) {
        selectizeControl.addOption({ value: id, text: displayLabel, code: label, alias_code: alias });
      } else {
        selectizeControl.options[id].code = label;
        selectizeControl.options[id].alias_code = alias;
        selectizeControl.options[id].text = displayLabel;
      }
      selectizeControl.addItem(id);

      const nativeOption = selectizeControl.$input[0].querySelector(`option[value="${id}"]`);
      if (nativeOption) {
        nativeOption.dataset.code = label;
        nativeOption.dataset.aliasCode = alias;
        nativeOption.textContent = displayLabel;
      }
    });

    updateTubeCount();
    $(`#${modalId}`).addClass('hidden');
  });

  // Sync count if user manually removes or adds items from Selectize input
  selectizeControl.on('item_remove', updateTubeCount);
  selectizeControl.on('item_add', updateTubeCount);

  // Master checkbox toggles all checkboxes
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
        pendingAliasesById[id] = getAliasFromRow(checkbox);
      } else {
        pendingSelectedIds.delete(id);
        delete pendingAliasesById[id];
      }
    });
    updateModalCheckboxCount();
  });

  // Sync master checkbox & update modal count when any checkbox changes
  $(document).on('change', `#${modalId} .${checkboxClass}`, function () {
    const allChecked = $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
    $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);

    const checkbox = $(this);
    const id = String(checkbox.val());
    if (checkbox.is(':checked')) {
      pendingSelectedIds.add(id);
      if (!pendingLabelsById[id]) {
        pendingLabelsById[id] = checkbox.closest('tr').children().eq(1).text().trim();
      }
      pendingAliasesById[id] = getAliasFromRow(checkbox);
    } else {
      pendingSelectedIds.delete(id);
      delete pendingAliasesById[id];
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

  // AJAX pagination inside modal, preserving filters
  $(document).on('click', `#${modalId} nav a[href]`, function (e) {
    e.preventDefault();
    const url = $(this).attr('href');
    if (!url) {
      return;
    }
    loadModalContent(url, { preservePage: true });
  });

  // Defensive: prevent any accidental form submit inside modal content
  $(document).on('submit', `#${modalId} form`, function (e) {
    e.preventDefault();
    e.stopPropagation();
  });

  // Initialize count
  updateTubeCount();
}

function toggleFields(selectedValue) {
    document.getElementById("animal_model").style.display = "none";
    document.getElementById("parasite_model").style.display = "none";
    document.getElementById("human_model").style.display = "none";
    document.getElementById("environment_model").style.display = "none";
    document.getElementById("pools_model").style.display = "none";

    if (selectedValue === "Animal samples") {
      document.getElementById("animal_model").style.display = "block";
    } else if (selectedValue === "Parasite samples") {
      document.getElementById("parasite_model").style.display = "block";
    } else if (selectedValue === "Human samples") {
      document.getElementById("human_model").style.display = "block";
    } else if (selectedValue === "Environment samples") {
      document.getElementById("environment_model").style.display = "block";
    } else if (selectedValue === "Pools") {
      document.getElementById("pools_model").style.display = "block";
    }
  }

  function toggleCultureStep(selectedValue) {
    document.getElementById("culture_primary").style.display = "none";
    document.getElementById("subculture").style.display = "none";
    document.getElementById("animal_model").style.display = "none";
    document.getElementById("parasite_model").style.display = "none";
    document.getElementById("human_model").style.display = "none";
    document.getElementById("environment_model").style.display = "none";
    document.getElementById("pools_model").style.display = "none";

    if (selectedValue === "Yes") {
      document.getElementById("culture_primary").style.display = "block";
    } else if (selectedValue === "No") {
      document.getElementById("subculture").style.display = "block";
    }
  }

// Get the success and error message elements from the DOM
const cultureSuccessMessage = document.getElementById('cultureSuccessMessage');
const cultureErrorMessage = document.getElementById('cultureErrorMessage');

// Show success message if it exists
if (cultureSuccessMessage) {
  Swal.fire({
    icon: 'success',
    title: 'Success',
    text: cultureSuccessMessage.textContent,
  });
}

// Show error message if it exists
if (cultureErrorMessage) {
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: cultureErrorMessage.textContent,
  });
}

// Function to generate next available culture code
function generateNextCultureCode() {
    const existingCodes = Array.from(document.querySelectorAll('.culture-code-input'))
        .map(input => input.value)
        .filter(code => code.startsWith(projectCode + '-CU-'));
    
    const usedNumbers = existingCodes.map(code => {
        const match = code.match(/-CU-(\d+)$/);
        return match ? parseInt(match[1]) : 0;
    }).sort((a, b) => a - b);

    let newSerial = 1;
    for (const num of usedNumbers) {
        if (num !== newSerial) break;
        newSerial++;
    }

    return `${projectCode}-CU-${newSerial}`;
}

// Function to update tube assignments display
function updateTubeAssignments() {
    const selectors = [
        { id: 'animal_tube_id', type: 'Animal tube' },
        { id: 'parasite_tube_id', type: 'Parasite tube' },
        { id: 'human_tube_id', type: 'Human tube' },
        { id: 'environment_tube_id', type: 'Environment tube' },
        { id: 'pool_tube_id', type: 'Pool tube' },
        { id: 'culture_id', type: 'Parent culture' },
    ];

    const $assignmentList = $('#tubes_assignment_list');
    const $assignmentSection = $('#selected_tubes_assignment');
    const autoFromParent = $('#auto_alias_from_parent').is(':checked');

    $assignmentList.empty();

    const selectedTubes = [];

    selectors.forEach(function (entry) {
        const control = $(`#${entry.id}`)[0]?.selectize;
        if (!control || !control.items) {
            return;
        }

        control.items.forEach(function (itemId) {
            const key = String(itemId);
            const opt = control.options[key] || {};
            const code = String(opt.code || opt.text || key);
            const parentAlias = String(opt.alias_code || '').trim();

            selectedTubes.push({
                id: key,
                code: code,
                type: entry.type,
                parentAlias: parentAlias,
            });
        });
    });

    if (selectedTubes.length === 0) {
        $assignmentSection.hide();
        return;
    }

    $assignmentSection.show();

    selectedTubes.forEach(function (tube, index) {
        const prefilledAlias = autoFromParent ? tube.parentAlias : '';
        const escapedAlias = prefilledAlias
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        const escapedCode = String(tube.code)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        const escapedParentAlias = tube.parentAlias
            ? String(tube.parentAlias).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            : '—';

        const codeOptions = availableCultureCodes.slice(0, 100).map((code, i) => {
            const selected = i === index ? 'selected' : '';
            return `<option value="${code}" ${selected}>${code}</option>`;
        }).join('');

        const rowHtml = `
            <tr class="bg-white">
                <td class="py-3 pr-4 font-medium text-gray-800">${escapedCode}</td>
                <td class="py-3 pr-4 text-gray-500">${tube.type}</td>
                <td class="py-3 pr-4 text-gray-600">${escapedParentAlias}</td>
                <td class="py-3 pr-4">
                    <select name="culture_codes[${tube.id}]"
                        class="culture-code-select w-full min-w-[140px] px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required>
                        <option value="">Select code</option>
                        ${codeOptions}
                    </select>
                </td>
                <td class="py-3">
                    <input type="text" name="culture_alias_codes[${tube.id}]" value="${escapedAlias}"
                        class="w-full min-w-[160px] px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Culture alias (optional)">
                </td>
            </tr>
        `;

        $assignmentList.append(rowHtml);
    });
}
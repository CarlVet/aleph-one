let boxVisualizationUpdateTimer = null;

function scheduleBoxVisualizationUpdate(delay = 50) {
  if (boxVisualizationUpdateTimer) {
    clearTimeout(boxVisualizationUpdateTimer);
  }

  boxVisualizationUpdateTimer = setTimeout(function () {
    if (typeof window.alephUpdateBoxVisualization === 'function') {
      window.alephUpdateBoxVisualization();
    }
  }, delay);
}

window.alephScheduleBoxVisualizationUpdate = scheduleBoxVisualizationUpdate;

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
  if (!table) {
    return;
  }

  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) {
    return;
  }

  const existing = thead.querySelector('tr[data-column-filters="1"]');
  if (existing) {
    existing.remove();
  }

  const headerRow = thead.querySelector('tr');
  if (!headerRow) {
    return;
  }

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
    input.value = (initialFilters && initialFilters[index]) ? String(initialFilters[index]) : '';
    input.className = 'w-full min-w-[10rem] rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
    filterTh.style.minWidth = '10rem';
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
  if (!table) {
    return;
  }

  const thead = table.querySelector('thead');
  if (!thead) {
    return;
  }

  const headerRow = thead.querySelector('tr');
  if (!headerRow) {
    return;
  }

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
  console.log('create-tube-positions.js loaded successfully');

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

  function initAjaxSelectize(selectId, placeholder, searchUrl) {
    $(`#${selectId}`).selectize({
      placeholder,
      create: false,
      maxOptions: 20,
      dropdownParent: 'body',
      openOnFocus: true,
      valueField: 'value',
      labelField: 'text',
      searchField: ['text', 'code', 'alias_code', 'sample_type_label'],
      preload: false,
      render: {
        option: function (item, escape) {
          const normalized = window.alephNormalizeTubeOption ? window.alephNormalizeTubeOption(item) : item;
          const primary = window.alephGetTubeDropdownLabel ? window.alephGetTubeDropdownLabel(normalized) : escape(normalized.text || '');
          const secondary = normalized.sample_type_label
            ? `<span class="ml-2 text-xs text-gray-500">· ${escape(normalized.sample_type_label)}</span>`
            : '';

          return `<div class="py-1 leading-tight"><span class="font-medium">${escape(primary)}</span>${secondary}</div>`;
        },
        item: function (item, escape) {
          const normalized = window.alephNormalizeTubeOption ? window.alephNormalizeTubeOption(item) : item;
          if (window.alephBuildTubeBadgeItemHtml) {
            return `<div>${window.alephBuildTubeBadgeItemHtml(normalized)}</div>`;
          }

          const label = window.alephGetTubeBadgeLabel ? window.alephGetTubeBadgeLabel(normalized) : escape(normalized.text || '');
          const secondary = normalized.sample_type_label
            ? ` <span class="text-white/90">· ${escape(normalized.sample_type_label)}</span>`
            : '';

          return `<div>${escape(label)}${secondary}</div>`;
        },
      },
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
          }
        });
      }
    });
  }

  initAjaxSelectize('human_tube_id', 'Enter human tube code', '/bank/tubes/create/tubes/human/search');
  initAjaxSelectize('animal_tube_id', 'Enter animal tube code', '/bank/tubes/create/tubes/animal/search');
  initAjaxSelectize('environment_tube_id', 'Enter environmental tube code', '/bank/tubes/create/tubes/environment/search');
  initAjaxSelectize('parasite_tube_id', 'Enter parasite tube code', '/bank/tubes/create/tubes/parasite/search');
  initAjaxSelectize('nucleic_tube_id', 'Enter nucleic tube code', '/bank/tubes/create/tubes/nucleic/search');
  initAjaxSelectize('culture_tube_id', 'Enter culture tube code', '/bank/tubes/create/tubes/culture/search');
  initAjaxSelectize('pool_tube_id', 'Enter pool tube code', '/bank/tubes/create/tubes/pool/search');

  $('#mover').selectize({
    placeholder: "Search and select reponsible person",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

   $('#reason').selectize({
    placeholder: "Select or enter reason of movement",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  if ($('#sub_project_id').length) {
    $('#sub_project_id').selectize({
      placeholder: 'Select sub-project',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button']
    });
  }

  // Initialize box selectize after a delay to ensure DOM is ready
  setTimeout(function() {
    console.log('Initializing box selectize...');
    
    // Get the original select element
    const boxSelect = document.getElementById('box');
    const originalOptions = Array.from(boxSelect.options);
    
    // Create options array with dimensions stored
    const selectizeOptions = originalOptions.map(option => ({
      value: option.value,
      text: option.text,
      rows: option.dataset.rows,
      columns: option.dataset.columns
    }));
    
    const boxSelectize = $('#box').selectize({
      placeholder: "Search and select box",
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
      options: selectizeOptions,
      onChange: function(value) {
        console.log('Box selection changed via selectize:', value);
        console.log('Selected option:', this.options[value]);
        console.log('All options:', this.options);
        window.__boxPreviewCellSizeManual = false;
        window.alephScheduleBoxVisualizationUpdate();
      }
    });
    
    // Store the selectize instance for later use
    window.boxSelectize = boxSelectize[0].selectize;
  }, 500);

  // Tables are server-paginated and loaded on-demand (no DataTables).

  function toggleFields(selectedValue) {
    document.getElementById("human_model").style.display = "none";
    document.getElementById("animal_model").style.display = "none";
    document.getElementById("environment_model").style.display = "none";
    document.getElementById("parasite_model").style.display = "none";
    document.getElementById("nucleic_model").style.display = "none";
    document.getElementById("culture_model").style.display = "none";
    document.getElementById("pool_model").style.display = "none";

    if (selectedValue === "Human samples") {
      document.getElementById("human_model").style.display = "block";
    } else if (selectedValue === "Animal samples") {
      document.getElementById("animal_model").style.display = "block";
    } else if (selectedValue === "Environmental samples") {
      document.getElementById("environment_model").style.display = "block";
    } else if (selectedValue === "Parasite samples") {
      document.getElementById("parasite_model").style.display = "block";
    } else if (selectedValue === "Nucleic acids") {
      document.getElementById("nucleic_model").style.display = "block";
    } else if (selectedValue === "Cultures") {
      document.getElementById("culture_model").style.display = "block";
    } else if (selectedValue === "Pools") {
      document.getElementById("pool_model").style.display = "block";
    }
  }

  setupTubeSelector({
    selectId: 'human_tube_id',
    modalId: 'human_tubes_modal',
    openBtnId: 'human_tubes_btn',
    closeBtnId: 'human_tubes_close_btn',
    confirmBtnId: 'confirm_human_tube_selection',
    checkboxClass: 'select-human-tube',
    masterCheckboxId: 'select_all_human_tubes',
    fetchUrl: '/bank/tubes/create/tubes/human'
  });

  setupTubeSelector({
    selectId: 'animal_tube_id',
    modalId: 'animal_tubes_modal',
    openBtnId: 'animal_tubes_btn',
    closeBtnId: 'animal_tubes_close_btn',
    confirmBtnId: 'confirm_tube_selection',
    checkboxClass: 'select-tube',
    masterCheckboxId: 'select_all_tubes',
    fetchUrl: '/bank/tubes/create/tubes/animal'
  });

  setupTubeSelector({
    selectId: 'environment_tube_id',
    modalId: 'environment_tubes_modal',
    openBtnId: 'environment_tubes_btn',
    closeBtnId: 'environment_tubes_close_btn',
    confirmBtnId: 'confirm_environment_tube_selection',
    checkboxClass: 'select-environment-tube',
    masterCheckboxId: 'select_all_environment_tubes',
    fetchUrl: '/bank/tubes/create/tubes/environment'
  });

  setupTubeSelector({
    selectId: 'parasite_tube_id',
    modalId: 'parasite_tubes_modal',
    openBtnId: 'parasite_tubes_btn',
    closeBtnId: 'parasite_tubes_close_btn',
    confirmBtnId: 'confirm_parasite_tube_selection',
    checkboxClass: 'select-parasite-tube',
    masterCheckboxId: 'select_all_parasite_tubes',
    fetchUrl: '/bank/tubes/create/tubes/parasite'
  });

  setupTubeSelector({
    selectId: 'nucleic_tube_id',
    modalId: 'nucleic_tubes_modal',
    openBtnId: 'nucleic_tubes_btn',
    closeBtnId: 'nucleic_tubes_close_btn',
    confirmBtnId: 'confirm_na_tube_selection',
    checkboxClass: 'select-na-tube',
    masterCheckboxId: 'select_all_na_tubes',
    fetchUrl: '/bank/tubes/create/tubes/nucleic'
  });

  setupTubeSelector({
    selectId: 'culture_tube_id',
    modalId: 'culture_tubes_modal',
    openBtnId: 'culture_tubes_btn',
    closeBtnId: 'culture_tubes_close_btn',
    confirmBtnId: 'confirm_culture_tube_selection',
    checkboxClass: 'select-culture-tube',
    masterCheckboxId: 'select_all_culture_tubes',
    fetchUrl: '/bank/tubes/create/tubes/culture'
  });

  setupTubeSelector({
    selectId: 'pool_tube_id',
    modalId: 'pool_tubes_modal',
    openBtnId: 'pool_tubes_btn',
    closeBtnId: 'pool_tubes_close_btn',
    confirmBtnId: 'confirm_pool_tube_selection',
    checkboxClass: 'select-pool-tube',
    masterCheckboxId: 'select_all_pool_tubes',
    fetchUrl: '/bank/tubes/create/tubes/pool'
  });

  document.getElementById('boxes_btn').addEventListener('click', function () {
  document.getElementById('boxes_modal').classList.remove('hidden');
})

document.getElementById('boxes_close_btn').addEventListener('click', function () {
  document.getElementById('boxes_modal').classList.add('hidden');
})

  
  // Get the Selectize instance correctly
  var selectizeInstance = $modelSelect[0].selectize;
  var initialValue = selectizeInstance.getValue(); // Get the initial value
  toggleFields(initialValue); // Initialize fields
  window.alephRefreshAllTubeBadgeDisplays?.();
});

function setupTubeSelector({
  selectId,
  modalId,
  openBtnId,
  closeBtnId,
  confirmBtnId,
  checkboxClass,
  masterCheckboxId,
  fetchUrl
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
  let pendingSampleTypeById = {};
  
  function updateTubeCount() {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
    window.alephConfigureTubeBadgeSelectize?.(selectId);
  }

  // Update count inside modal (checkboxes checked)
  function updateModalCheckboxCount() {
    $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
  }

  function syncPendingFromSelectize() {
    pendingSelectedIds = new Set(selectizeControl.items.map(String));
    pendingLabelsById = {};
    pendingAliasesById = {};
    pendingSampleTypeById = {};
    selectizeControl.items.forEach(function (id) {
      const key = String(id);
      const opt = selectizeControl.options[key];
      if (opt && opt.text) {
        pendingLabelsById[key] = String(opt.code || opt.text);
        if (opt.alias_code) {
          pendingAliasesById[key] = String(opt.alias_code);
        }
        if (opt.sample_type_label) {
          pendingSampleTypeById[key] = String(opt.sample_type_label);
        }
      }
    });
  }

  function readCheckboxSampleTypeLabel(checkbox) {
    return String(checkbox.dataset.sampleTypeLabel || checkbox.getAttribute('data-sample-type-label') || '').trim();
  }

  function readTubeRowMeta(checkbox) {
    const row = checkbox.closest('tr');
    if (!row) {
      return { code: '', alias: '' };
    }

    const codeCell = row.children[1];
    const aliasCell = row.children[2];
    const codeLink = codeCell?.querySelector('a');
    const code = String(codeLink?.textContent || codeCell?.textContent || '').trim();
    let alias = String(aliasCell?.textContent || '').trim();
    if (alias === 'N/A') {
      alias = '';
    }

    return { code, alias };
  }

  function rememberPendingTubeSelection(id, checkbox) {
    const meta = readTubeRowMeta(checkbox);
    if (!pendingLabelsById[id]) {
      pendingLabelsById[id] = meta.code;
      pendingAliasesById[id] = meta.alias;
      pendingSampleTypeById[id] = readCheckboxSampleTypeLabel(checkbox);
    }
  }

  function normalizeTubeAlias(alias) {
    const normalized = String(alias || '').trim();
    return normalized === 'N/A' ? '' : normalized;
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

  // Show modal (load on-demand)
  $(`#${openBtnId}`).on('click', function () {
    $(`#${modalId}`).show();
    syncPendingFromSelectize();
    loadModalContent(fetchUrl, { preservePage: false });
  });

  // Close modal - handle both main close button and cancel buttons
  $(`#${closeBtnId}`).on('click', function () {
    $(`#${modalId}`).hide();
  });
  
  // Cancel button - use event delegation
  $(document).on('click', `#${modalId} button[id$="_cancel_btn"]`, function () {
    $(`#${modalId}`).hide();
  });

  // Confirm selection (sync Selectize with checked checkboxes)
  $(document).on('click', `#${modalId} #${confirmBtnId}`, function (e) {
    e.preventDefault();
    e.stopPropagation();
    selectizeControl.clear(true);

    Array.from(pendingSelectedIds).forEach(function (id) {
      const tubeCode = String(pendingLabelsById[id] || (selectizeControl.options[id] ? (selectizeControl.options[id].code || selectizeControl.options[id].text) : id)).trim();
      const tubeAlias = normalizeTubeAlias(pendingAliasesById[id] || (selectizeControl.options[id] ? (selectizeControl.options[id].alias_code || '') : ''));
      const sampleTypeLabel = pendingSampleTypeById[id] || (selectizeControl.options[id] ? selectizeControl.options[id].sample_type_label : '');
      if (!selectizeControl.options[id]) {
        selectizeControl.addOption({
          value: id,
          text: tubeCode,
          code: tubeCode,
          alias_code: tubeAlias,
          sample_type_label: sampleTypeLabel,
        });
      } else {
        selectizeControl.options[id].code = tubeCode;
        selectizeControl.options[id].alias_code = tubeAlias;
        selectizeControl.options[id].text = tubeCode;
        selectizeControl.options[id].sample_type_label = sampleTypeLabel;
      }
      selectizeControl.addItem(id, true);

      const nativeOption = Array.from(selectizeControl.$input[0].options).find((option) => String(option.value) === String(id));
      if (nativeOption) {
        nativeOption.dataset.code = tubeCode;
        nativeOption.dataset.aliasCode = tubeAlias;
        nativeOption.textContent = tubeCode;
      } else if (tubeCode !== '') {
        const optionElement = document.createElement('option');
        optionElement.value = String(id);
        optionElement.textContent = tubeCode;
        optionElement.dataset.code = tubeCode;
        optionElement.dataset.aliasCode = tubeAlias;
        selectizeControl.$input[0].appendChild(optionElement);
      }
    });

    window.alephConfigureTubeBadgeSelectize?.(selectId);
    window.alephUpdateTubeDisplayTogglePlacement?.();
    updateTubeCount();
    window.alephScheduleBoxVisualizationUpdate?.(100);
    $(`#${modalId}`).hide();
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
        rememberPendingTubeSelection(id, checkbox.get(0));
      } else {
        pendingSelectedIds.delete(id);
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
      rememberPendingTubeSelection(id, checkbox.get(0));
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

  // Init count outside modal (Selectize)
  updateTubeCount();
}



$(document).ready(function () {
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
});

// Initialize box visualization when page loads
$(document).ready(function() {
  // Initialize box visualization after a delay to ensure all elements are loaded
  setTimeout(function() {
    console.log('Initializing box visualization...');
    
    // Debug: Log all available boxes and their data attributes
    const boxSelect = document.getElementById('box');
    if (boxSelect) {
      console.log('Box select element found');
      console.log('Box select options length:', boxSelect.options.length);
      console.log('Available boxes:');
      Array.from(boxSelect.options).forEach((option, index) => {
        console.log(`Box ${index}:`, {
          value: option.value,
          text: option.text,
          rows: option.dataset.rows,
          columns: option.dataset.columns
        });
      });
      
      // Only show "no boxes" message if there are actually no boxes (excluding the "Select a box" option)
      if (boxSelect.options.length <= 1) { // Only the "Select a box" option
        console.log('No boxes detected in select element');
        const boxGrid = document.getElementById('boxGrid');
        if (boxGrid) {
          boxGrid.innerHTML = '<p class="text-orange-500 text-center">No boxes available. Please select a project first.</p>';
        }
      } else {
        console.log('Boxes detected in select element:', boxSelect.options.length - 1);
        // Initialize the visualization with empty state
        const boxGrid = document.getElementById('boxGrid');
        if (boxGrid) {
          boxGrid.innerHTML = '<p class="text-gray-500 text-center">Select a box to see layout</p>';
        }
      }
    } else {
      console.log('Box select element not found');
    }
    
    // Don't call updateBoxVisualization here - wait for user to select a box
  }, 1000);
});

const BOX_PREVIEW_CELL_MIN = 28;
const BOX_PREVIEW_CELL_MAX = 240;
const BOX_PREVIEW_CELL_DEFAULT = 52;
const BOX_PREVIEW_LABEL_FONT = '600 10px ui-sans-serif, system-ui, -apple-system, sans-serif';
const BOX_PREVIEW_CELL_PADDING = 12;

function escapeBoxPreviewText(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function getBoxPreviewCellSize() {
  const slider = document.getElementById('boxPreviewCellSize');
  if (slider) {
    const parsed = parseInt(slider.value, 10);
    if (!Number.isNaN(parsed)) {
      return Math.max(BOX_PREVIEW_CELL_MIN, Math.min(BOX_PREVIEW_CELL_MAX, parsed));
    }
  }

  return window.__boxPreviewCellSize || BOX_PREVIEW_CELL_DEFAULT;
}

function measureBoxPreviewLabelWidth(text) {
  if (!window.__boxPreviewMeasureCanvas) {
    window.__boxPreviewMeasureCanvas = document.createElement('canvas');
  }

  const ctx = window.__boxPreviewMeasureCanvas.getContext('2d');
  if (!ctx) {
    return String(text || '').length * 6;
  }

  ctx.font = BOX_PREVIEW_LABEL_FONT;

  return Math.ceil(ctx.measureText(String(text || '')).width);
}

function computeFitBoxPreviewCellSize(labels) {
  const texts = (labels || [])
    .map((value) => String(value || '').trim())
    .filter((value) => value !== '');

  if (texts.length === 0) {
    texts.push('10,10');
  }

  const maxTextWidth = Math.max(
    ...texts.map((text) => measureBoxPreviewLabelWidth(text)),
    measureBoxPreviewLabelWidth('10,10')
  );

  const fitted = maxTextWidth + BOX_PREVIEW_CELL_PADDING;

  return Math.max(BOX_PREVIEW_CELL_MIN, Math.min(BOX_PREVIEW_CELL_MAX, fitted));
}

function applyFitBoxPreviewCellSize(labels) {
  const size = computeFitBoxPreviewCellSize(labels);
  const slider = document.getElementById('boxPreviewCellSize');
  const label = document.getElementById('boxPreviewCellSizeLabel');

  if (slider) {
    slider.max = String(Math.max(parseInt(slider.max, 10) || BOX_PREVIEW_CELL_MAX, size));
    slider.value = String(Math.min(size, parseInt(slider.max, 10) || BOX_PREVIEW_CELL_MAX));
  }

  window.__boxPreviewCellSize = size;

  if (label) {
    label.textContent = `${size}px`;
  }

  return size;
}

function buildBoxPreviewAssignments(selectedTubes, startX, startY, rows, columns) {
  const assignments = {};
  let currentX = startX;
  let currentY = startY;

  selectedTubes.forEach((tube) => {
    if (currentY > rows) {
      return;
    }

    assignments[`${currentX}-${currentY}`] = tube;
    currentX++;

    if (currentX > columns) {
      currentX = 1;
      currentY++;
    }
  });

  return assignments;
}

function resolveOccupiedDisplayLabel(occupiedHere, displayMode) {
  const code = occupiedHere?.tube_code || '';
  const alias = occupiedHere?.tube_alias_code || '';

  return (displayMode === 'alias' && alias && alias !== 'N/A') ? alias : code;
}

function collectBoxPreviewDisplayLabels(assignments, occupiedMap, displayMode, rows, columns) {
  const labels = [`${columns},${rows}`];

  Object.entries(assignments).forEach(([, tube]) => {
    const display = tube?.display_code || tube?.code;
    if (display) {
      labels.push(display);
    }
  });

  Object.entries(occupiedMap || {}).forEach(([key, occupiedHere]) => {
    if (assignments[key]) {
      return;
    }

    const display = resolveOccupiedDisplayLabel(occupiedHere, displayMode);
    if (display) {
      labels.push(display);
    }
  });

  return labels;
}

function applyBoxPreviewGridDimensions(boxGrid, columns, rows, boxCellPx) {
  const gridWidth = (columns * boxCellPx) + Math.max(0, columns - 1) * 4;

  boxGrid.style.gridTemplateColumns = `repeat(${columns}, ${boxCellPx}px)`;
  boxGrid.style.gridTemplateRows = `repeat(${rows}, ${boxCellPx}px)`;
  boxGrid.style.width = `${gridWidth}px`;
}

function shouldTruncateBoxPreviewLabel(displayText, boxCellPx) {
  const needed = measureBoxPreviewLabelWidth(displayText) + BOX_PREVIEW_CELL_PADDING;

  return boxCellPx < needed;
}

function syncBoxPreviewCellSizeLabel() {
  const slider = document.getElementById('boxPreviewCellSize');
  const label = document.getElementById('boxPreviewCellSizeLabel');

  if (slider && label) {
    label.textContent = `${slider.value}px`;
  }
}

function buildBoxPreviewBadgeHtml(displayText, colorClass, extraBorder = '', truncate = false) {
  const safe = escapeBoxPreviewText(displayText);
  const truncateClass = truncate ? 'truncate' : 'whitespace-nowrap';

  return `<div class="box-preview-badge flex h-full w-full min-h-0 items-center justify-center overflow-hidden rounded-md border-2 bg-gradient-to-br px-1 py-0.5 shadow-sm ${colorClass} ${extraBorder}">
    <span class="block w-full text-center text-[10px] font-semibold leading-tight ${truncateClass}">${safe}</span>
  </div>`;
}

function applyBoxPreviewCellDimensions(cell, boxCellPx) {
  cell.style.width = `${boxCellPx}px`;
  cell.style.height = `${boxCellPx}px`;
  cell.style.minWidth = `${boxCellPx}px`;
  cell.style.minHeight = `${boxCellPx}px`;
}

// Box visualization functionality
function updateBoxVisualization() {
  console.log('updateBoxVisualization called');
  window.__boxVisualizationRequestId = (window.__boxVisualizationRequestId || 0) + 1;
  const requestId = window.__boxVisualizationRequestId;
  
  const boxSelect = document.getElementById('box');
  const boxGrid = document.getElementById('boxGrid');
  const xPosition = document.getElementById('x_position');
  const yPosition = document.getElementById('y_position');
  const boxVisualization = document.getElementById('boxVisualization');
  const displayMode = resolveTubeBadgeDisplayMode();
  
  console.log('Box select:', boxSelect);
  console.log('Box grid:', boxGrid);
  console.log('X position:', xPosition);
  console.log('Y position:', yPosition);
  
  if (!boxSelect || !boxGrid) {
    console.log('Required elements not found');
    return;
  }
  
  // Get the Selectize instance
  const boxSelectize = window.boxSelectize || boxSelect.selectize;
  console.log('Box selectize instance:', boxSelectize);
  
  if (!boxSelectize || !boxSelectize.getValue()) {
    console.log('No box selected or no selectize instance');
    boxGrid.innerHTML = '<p class="text-gray-500 text-center">Select a box to see layout</p>';
    return;
  }

  // Get the selected option from Selectize
  const selectedValue = boxSelectize.getValue();
  const selectedOption = boxSelectize.options[selectedValue];
  
  console.log('Selected value:', selectedValue);
  console.log('Selected option:', selectedOption);
  
  if (!selectedOption) {
    console.log('No selected option found');
    boxGrid.innerHTML = '<p class="text-red-500 text-center">Invalid box selection</p>';
    return;
  }

  // Try to get dimensions from the original option element first
  const originalOption = Array.from(boxSelect.options).find(opt => opt.value === selectedValue);
  console.log('Original option:', originalOption);
  
  let rows, columns;
  
  // First try to get dimensions from Selectize options
  if (selectedOption && selectedOption.rows && selectedOption.columns) {
    rows = parseInt(selectedOption.rows);
    columns = parseInt(selectedOption.columns);
    console.log('Dimensions from Selectize options:', rows, 'x', columns);
  } else if (originalOption && originalOption.dataset.rows && originalOption.dataset.columns) {
    rows = parseInt(originalOption.dataset.rows);
    columns = parseInt(originalOption.dataset.columns);
    console.log('Dimensions from data attributes:', rows, 'x', columns);
  } else {
    // Fallback: try to parse from the option text
    const text = selectedOption.text;
    console.log('Parsing dimensions from text:', text);
    const match = text.match(/\((\d+)x(\d+)\)/);
    if (match) {
      rows = parseInt(match[1]);
      columns = parseInt(match[2]);
      console.log('Dimensions from text parsing:', rows, 'x', columns);
    } else {
      console.log('Could not parse dimensions from text:', text);
    }
  }

  console.log('Final box dimensions:', rows, 'x', columns);

  if (!rows || !columns) {
    console.log('Invalid dimensions detected');
    boxGrid.innerHTML = '<p class="text-red-500 text-center">Invalid box dimensions</p>';
    return;
  }

  // Update position limits
  if (xPosition) xPosition.max = columns;
  if (yPosition) yPosition.max = rows;

  // Cache occupancy per selected box to avoid refetching on every keystroke.
  window.__boxOccupancyCache = window.__boxOccupancyCache || {};
  const cacheKey = String(selectedValue || '');

  const renderGrid = (occupied) => {
    if (requestId !== window.__boxVisualizationRequestId) {
      return;
    }

    boxGrid.innerHTML = '';

    const occupiedMap = {};
    (occupied || []).forEach((row) => {
      const x = Number(row.position_x);
      const y = Number(row.position_y);
      if (!x || !y) return;
      occupiedMap[`${x}-${y}`] = row;
    });

    const selectedTubes = getAllSelectedTubes();
    console.log('Selected tubes:', selectedTubes);

    const startX = parseInt(xPosition?.value) || 1;
    const startY = parseInt(yPosition?.value) || 1;
    const assignments = buildBoxPreviewAssignments(selectedTubes, startX, startY, rows, columns);
    const replacements = [];

    Object.entries(assignments).forEach(([key, tube]) => {
      const [x, y] = key.split('-').map((value) => parseInt(value, 10));
      const occupiedHere = occupiedMap[key];
      const existingCode = occupiedHere ? (occupiedHere.tube_code || '') : '';
      const existingDisplay = resolveOccupiedDisplayLabel(occupiedHere, displayMode);
      const assignedDisplay = tube.display_code || tube.code;

      if (existingCode && existingCode !== tube.code) {
        replacements.push({
          x,
          y,
          new_tube: assignedDisplay,
          old_tube: existingDisplay,
        });
      }
    });

    let boxCellPx;
    if (!window.__boxPreviewCellSizeManual) {
      const labels = collectBoxPreviewDisplayLabels(assignments, occupiedMap, displayMode, rows, columns);
      boxCellPx = applyFitBoxPreviewCellSize(labels);
    } else {
      boxCellPx = getBoxPreviewCellSize();
    }

    applyBoxPreviewGridDimensions(boxGrid, columns, rows, boxCellPx);

    // Expose replacements for any optional submit-confirm flows.
    window.__tubePositionReplacements = replacements;

    for (let y = 1; y <= rows; y++) {
      for (let x = 1; x <= columns; x++) {
        const cell = document.createElement('div');
        cell.className = 'relative flex items-center justify-center overflow-hidden border border-gray-300 bg-gray-50 p-0.5 text-xs';
        applyBoxPreviewCellDimensions(cell, boxCellPx);
        cell.id = `cell-${x}-${y}`;

        const key = `${x}-${y}`;
        const assignedTube = assignments[key];
        const occupiedHere = occupiedMap[key];

        // If this position is part of the new registration preview, show it even if occupied.
        if (assignedTube) {
          const tube = assignedTube;
          
          // Determine color based on tube type
          let colorClass = 'from-blue-100 to-blue-200 border-blue-400 text-blue-900';
          if (tube.tubes_content_type) {
            const type = tube.tubes_content_type.toLowerCase();
            if (type.includes('human')) {
              colorClass = 'from-pink-100 to-pink-200 border-pink-400 text-pink-900';
            } else if (type.includes('animal')) {
              colorClass = 'from-green-100 to-green-200 border-green-400 text-green-900';
            } else if (type.includes('environment')) {
              colorClass = 'from-teal-100 to-teal-200 border-teal-400 text-teal-900';
            } else if (type.includes('parasite')) {
              colorClass = 'from-purple-100 to-purple-200 border-purple-400 text-purple-900';
            } else if (type.includes('nucleic')) {
              colorClass = 'from-indigo-100 to-indigo-200 border-indigo-400 text-indigo-900';
            } else if (type.includes('culture')) {
              colorClass = 'from-orange-100 to-orange-200 border-orange-400 text-orange-900';
            } else if (type.includes('pool')) {
              colorClass = 'from-cyan-100 to-cyan-200 border-cyan-400 text-cyan-900';
            }
          }
          
          const existingCode = occupiedHere ? (occupiedHere.tube_code || '') : '';
          const existingDisplay = resolveOccupiedDisplayLabel(occupiedHere, displayMode);
          const assignedDisplay = tube.display_code || tube.code;
          const isReplacing = Boolean(existingCode && existingCode !== tube.code);
          const extraBorder = isReplacing ? 'ring-2 ring-red-400 border-red-500' : '';

          cell.innerHTML = buildBoxPreviewBadgeHtml(
            assignedDisplay,
            colorClass,
            extraBorder,
            shouldTruncateBoxPreviewLabel(assignedDisplay, boxCellPx)
          );
          cell.title = isReplacing
            ? `Override: ${assignedDisplay} will replace ${existingDisplay} at (${x},${y})`
            : `New position: ${assignedDisplay} - (${x},${y})`;
        } else {
          if (occupiedHere) {
            const display = resolveOccupiedDisplayLabel(occupiedHere, displayMode);
            cell.innerHTML = buildBoxPreviewBadgeHtml(
              display,
              'from-gray-200 to-gray-300 border-gray-400 text-gray-900',
              '',
              shouldTruncateBoxPreviewLabel(display, boxCellPx)
            );
            cell.title = `Currently in box: ${display} - Position: (${x},${y})`;
          } else {
            cell.textContent = `${x},${y}`;
            cell.className = 'relative flex items-center justify-center overflow-hidden border border-gray-300 bg-gray-50 p-0.5 text-[10px] text-gray-500';
            applyBoxPreviewCellDimensions(cell, boxCellPx);
          }
        }
        
        boxGrid.appendChild(cell);
      }
    }

    // Remove any existing info div
    const existingInfo = boxGrid.parentNode.querySelector('.tube-info');
    if (existingInfo) {
      existingInfo.remove();
    }
    const existingWarn = boxGrid.parentNode.querySelector('.tube-replace-warning');
    if (existingWarn) {
      existingWarn.remove();
    }

    // Show tube count info
    const infoDiv = document.createElement('div');
    infoDiv.className = 'tube-info mt-2 text-sm text-gray-600';
    infoDiv.innerHTML = `
      <p><strong>Selected tubes:</strong> ${selectedTubes.length}</p>
      <p><strong>Starting position:</strong> (${startX}, ${startY})</p>
      <p><strong>Box capacity:</strong> ${rows * columns} positions</p>
      <p><strong>Occupied (current):</strong> ${Object.keys(occupiedMap).length}</p>
    `;
    boxGrid.parentNode.appendChild(infoDiv);

    if (replacements.length > 0) {
      const warnDiv = document.createElement('div');
      warnDiv.className = 'tube-replace-warning mt-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800';
      const previewLines = replacements
        .slice(0, 5)
        .map((r) => `<li><strong>${r.new_tube}</strong> will replace <strong>${r.old_tube}</strong> at (${r.x},${r.y})</li>`)
        .join('');
      warnDiv.innerHTML = `
        <div class="font-semibold">Replacement warning</div>
        <div class="mt-1">${replacements.length} tube position${replacements.length !== 1 ? 's' : ''} will override existing tubes in this box.</div>
        <ul class="mt-2 list-disc pl-5 text-xs">${previewLines}${replacements.length > 5 ? '<li>…</li>' : ''}</ul>
      `;
      boxGrid.parentNode.appendChild(warnDiv);
    }
  };

  const cached = window.__boxOccupancyCache[cacheKey];
  if (cached) {
    renderGrid(cached);
    return;
  }

  if (boxVisualization) {
    boxVisualization.classList.add('opacity-75');
  }

  fetch(`/bank/boxes/${encodeURIComponent(cacheKey)}/latest-tube-positions`)
    .then((r) => r.json())
    .then((data) => {
      const occupied = (data && data.occupied) ? data.occupied : [];
      window.__boxOccupancyCache[cacheKey] = occupied;
      renderGrid(occupied);
    })
    .catch(() => {
      window.__boxOccupancyCache[cacheKey] = [];
      renderGrid([]);
    })
    .finally(() => {
      if (boxVisualization) {
        boxVisualization.classList.remove('opacity-75');
      }
    });

}

window.alephUpdateBoxVisualization = updateBoxVisualization;

function resolveTubeBadgeDisplayMode() {
  if (typeof window.alephGetTubeBadgeDisplayMode === 'function') {
    return window.alephGetTubeBadgeDisplayMode();
  }

  const checked = document.querySelector('input[name="tube_badge_display"]:checked, input[name="tube_code_display"]:checked');

  return checked && checked.value === 'alias' ? 'alias' : 'tube';
}

function findNativeTubeOption(selectElement, itemId) {
  if (!selectElement) {
    return null;
  }

  const itemValue = String(itemId);

  return Array.from(selectElement.options).find((option) => String(option.value) === itemValue) || null;
}

function resolveTubeOptionDetails(selectize, itemId, selectElement) {
  const nativeOption = findNativeTubeOption(selectElement, itemId);
  const option = selectize.options[itemId] || {};
  const code = String(option.code || nativeOption?.dataset.code || '').trim();
  const alias = String(option.alias_code || nativeOption?.dataset.aliasCode || '').trim();
  const displayMode = resolveTubeBadgeDisplayMode();
  const display = (displayMode === 'alias' && alias && alias !== 'N/A')
    ? alias
    : (code || String(option.text || nativeOption?.textContent || '').trim());

  return {
    code,
    alias_code: alias,
    display_code: display,
  };
}

function getAllSelectedTubes() {
  const tubes = [];
  const displayMode = resolveTubeBadgeDisplayMode();

  const selectIds = [
    'human_tube_id',
    'animal_tube_id',
    'environment_tube_id',
    'parasite_tube_id',
    'nucleic_tube_id',
    'culture_tube_id',
    'pool_tube_id',
  ];

  selectIds.forEach((id) => {
    const selectElement = document.getElementById(id);
    const selectize = $(`#${id}`)[0]?.selectize;
    if (!selectize || selectize.items.length === 0) {
      return;
    }

    selectize.items.forEach((itemId) => {
      const details = resolveTubeOptionDetails(selectize, itemId, selectElement);
      if (!details.display_code) {
        return;
      }

      const tubesContentTypeHint = id.replace('_tube_id', '');
      tubes.push({
        id: itemId,
        code: details.code,
        alias_code: details.alias_code,
        display_code: details.display_code,
        tubes_content_type: tubesContentTypeHint,
      });
    });
  });

  console.log('Total selected tubes:', tubes.length, 'display mode:', displayMode);
  return tubes;
}

function onTubeBadgeDisplayModeChange() {
  window.__boxPreviewCellSizeManual = false;

  if (typeof window.alephRefreshAllTubeBadgeDisplays === 'function') {
    window.alephRefreshAllTubeBadgeDisplays();
  }

  if (typeof window.alephUpdateBoxVisualization === 'function') {
    window.alephUpdateBoxVisualization();
    return;
  }

  window.alephScheduleBoxVisualizationUpdate?.(0);
}

window.alephHandleTubeBadgeDisplayChange = onTubeBadgeDisplayModeChange;
window.alephOnTubeBadgeDisplayModeChange = onTubeBadgeDisplayModeChange;

function bindTubeBadgeDisplayToggleListeners() {
  const toggle = document.getElementById('tube-badge-display-toggle');
  if (!toggle || toggle.dataset.badgeModeListenersBound === '1') {
    return;
  }

  toggle.dataset.badgeModeListenersBound = '1';

  toggle.addEventListener('change', function (event) {
    const target = event.target;
    if (!target || target.type !== 'radio') {
      return;
    }

    if (target.name !== 'tube_badge_display' && target.name !== 'tube_code_display') {
      return;
    }

    onTubeBadgeDisplayModeChange();
  });
}

window.alephBindTubeBadgeDisplayToggleListeners = bindTubeBadgeDisplayToggleListeners;

// Update visualization when position changes
$(document).on('input', '#x_position, #y_position', function() {
  window.alephScheduleBoxVisualizationUpdate();
});

// Update visualization when box selection changes
$(document).on('change', '#box', function() {
  window.alephScheduleBoxVisualizationUpdate();
});

// Update visualization when tube selection changes
$(document).on('item_add item_remove', '.selectize-input', function() {
  window.alephScheduleBoxVisualizationUpdate(100);
});

// Test visualization button
$(document).on('input', '#boxPreviewCellSize', function () {
  window.__boxPreviewCellSizeManual = true;
  syncBoxPreviewCellSizeLabel();
  window.alephScheduleBoxVisualizationUpdate(50);
});

$(document).on('click', '#boxPreviewFitWidth', function () {
  window.__boxPreviewCellSizeManual = false;
  window.alephScheduleBoxVisualizationUpdate(50);
});

$(document).on('click', '#testVisualization', function() {
  window.alephScheduleBoxVisualizationUpdate(0);
});

document.addEventListener('DOMContentLoaded', function () {
  bindTubeBadgeDisplayToggleListeners();

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'boxes_lookup',
      rowsKey: 'boxLookupRows',
      selectFieldId: 'box',
      valueKey: 'id',
      labelKey: 'label',
      columns: [
        { key: 'code', hrefTemplate: '/bank/boxes/{id}/contents', hrefKey: 'id' },
        { key: 'name' },
        { key: 'alias_code' },
        { key: 'content_type' },
        { key: 'dimensions' },
      ],
      onSelect: function () {
        if (typeof window.alephScheduleBoxVisualizationUpdate === 'function') {
          window.alephScheduleBoxVisualizationUpdate(50);
        }
      },
    });
  }
});

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
  const $modelSelect = $('#model').selectize({
    placeholder: 'Select sample origin',
    create: false,
    dropdownParent: 'body',
    onChange: function (value) {
      toggleFields(value);
      updateSampleAssignments();
    },
  });

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

    return $el[0].selectize;
  }

  const humanSelectize = initAjaxSelectize(
    'human_sample_id',
    'Enter human sample code',
    '/samples/parasites/create/samples/human/search'
  );
  const animalSelectize = initAjaxSelectize(
    'animal_sample_id',
    'Enter animal sample code',
    '/samples/parasites/create/samples/animal/search'
  );
  const environmentSelectize = initAjaxSelectize(
    'environment_sample_id',
    'Enter environment sample code',
    '/samples/parasites/create/samples/environment/search'
  );

  $('#parasite_species').selectize({
    placeholder: 'Search parasite species',
    create: false,
    plugins: ['remove_button'],
  });

  $('#parasite_lab').selectize({
    placeholder: 'Search or enter laboratory',
    create: true,
    plugins: ['remove_button'],
  });

  $('#parasite_state').selectize({
    placeholder: 'Search state of parasite',
    create: true,
    plugins: ['remove_button'],
  });

  $('#identificator').selectize({
    placeholder: 'Select person who identified the parasite',
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

  function toggleFields(selectedValue) {
    document.getElementById('human_model').style.display = 'none';
    document.getElementById('animal_model').style.display = 'none';
    document.getElementById('environment_model').style.display = 'none';

    if (selectedValue === 'Human samples') {
      document.getElementById('human_model').style.display = 'block';
    } else if (selectedValue === 'Animal samples') {
      document.getElementById('animal_model').style.display = 'block';
    } else if (selectedValue === 'Environmental samples') {
      document.getElementById('environment_model').style.display = 'block';
    }
  }

  // Parasite species modal setup
  document.getElementById('parasite_species_form_btn')?.addEventListener('click', function () {
    document.getElementById('parasite_species_form_modal')?.classList.remove('hidden');
  });
  document.getElementById('parasite_species_form_close_btn')?.addEventListener('click', function () {
    document.getElementById('parasite_species_form_modal')?.classList.add('hidden');
  });

  // Parasite lab modal setup
  document.getElementById('parasite_lab_form_btn')?.addEventListener('click', function () {
    document.getElementById('parasite_lab_form_modal')?.classList.remove('hidden');
  });
  document.getElementById('parasite_lab_form_close_btn')?.addEventListener('click', function () {
    document.getElementById('parasite_lab_form_modal')?.classList.add('hidden');
  });

  function updateSelectedCount(selectizeControl, countSpanId) {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
  }

  function setupSampleSelector({
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
        const sampleCode =
          pendingLabelsById[id] ||
          (selectizeControl.options[id] ? (selectizeControl.options[id].code || selectizeControl.options[id].text) : id);

        if (!selectizeControl.options[id]) {
          selectizeControl.addOption({ value: id, text: sampleCode, code: sampleCode });
        }

        selectizeControl.addItem(id);
      });

      updateSelectedCount(selectizeControl, `${selectId}_count`);
      updateSampleAssignments();
      $(`#${modalId}`).hide();
    });

    selectizeControl.on('item_remove', function () {
      updateSelectedCount(selectizeControl, `${selectId}_count`);
      updateSampleAssignments();
    });
    selectizeControl.on('item_add', function () {
      updateSelectedCount(selectizeControl, `${selectId}_count`);
      updateSampleAssignments();
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

    updateSelectedCount(selectizeControl, `${selectId}_count`);
  }

  setupSampleSelector({
    selectizeControl: humanSelectize,
    selectId: 'human_sample_id',
    modalId: 'human_samples_modal',
    openBtnId: 'human_samples_btn',
    closeBtnId: 'human_samples_close_btn',
    confirmBtnId: 'confirm_human_sample_selection',
    checkboxClass: 'select-human-sample',
    masterCheckboxId: 'select_all_human_samples',
    fetchUrl: '/samples/parasites/create/samples/human',
  });

  setupSampleSelector({
    selectizeControl: animalSelectize,
    selectId: 'animal_sample_id',
    modalId: 'animal_samples_modal',
    openBtnId: 'animal_samples_btn',
    closeBtnId: 'animal_samples_close_btn',
    confirmBtnId: 'confirm_animal_sample_selection',
    checkboxClass: 'select-animal-sample',
    masterCheckboxId: 'select_all_animal_samples',
    fetchUrl: '/samples/parasites/create/samples/animal',
  });

  setupSampleSelector({
    selectizeControl: environmentSelectize,
    selectId: 'environment_sample_id',
    modalId: 'environment_samples_modal',
    openBtnId: 'environment_samples_btn',
    closeBtnId: 'environment_samples_close_btn',
    confirmBtnId: 'confirm_environment_sample_selection',
    checkboxClass: 'select-environment-sample',
    masterCheckboxId: 'select_all_environment_samples',
    fetchUrl: '/samples/parasites/create/samples/environment',
  });

  const selectizeInstance = $modelSelect[0].selectize;
  toggleFields(selectizeInstance.getValue());

  const successMessageElement = document.getElementById('successMessage');
  const errorMessageElement = document.getElementById('errorMessage');
  if (successMessageElement && typeof Swal !== 'undefined') {
    Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent });
  }
  if (errorMessageElement && typeof Swal !== 'undefined') {
    Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent });
  }

  function getSampleCode(selectizeControl, id) {
    const option = selectizeControl.options[id] || {};
    const raw = (option.code || option.text || '').toString();
    return raw.split('|')[0].trim();
  }

  function getUnusedParasiteCodes() {
    const usedCodes = Array.from(document.querySelectorAll('.parasite-code-select'))
      .map((select) => select.value)
      .filter((code) => code && code !== '');

    return (availableParasiteCodes || []).filter((code) => !usedCodes.includes(code));
  }

  function updateAvailableCodes() {
    const storageMode = (document.querySelector('input[name="storage_mode"]:checked')?.value || 'individual').toString();
    if (storageMode === 'pool') {
      return;
    }

    const usedCodes = Array.from(document.querySelectorAll('.parasite-code-select'))
      .map((select) => select.value)
      .filter((code) => code && code !== '');

    $('.parasite-code-select').each(function () {
      const $select = $(this);
      const currentValue = $select.val();
      const availableCodes = (availableParasiteCodes || []).filter((code) => !usedCodes.includes(code) || code === currentValue);

      const wasSelected = $select.val();

      $select.find('option:not(:first)').remove();
      availableCodes.forEach((code) => {
        $select.append(`<option value="${code}">${code}</option>`);
      });

      if (wasSelected && availableCodes.includes(wasSelected)) {
        $select.val(wasSelected);
      }
    });
  }

  function generateTickRows(sampleId, tickCount) {
    const $container = $(`.tick-count-input[data-sample-id="${sampleId}"]`).closest('.sample-container');
    const $tickRowsContainer = $container.find('.tick-rows-container');

    $tickRowsContainer.empty();

    const storageMode = (document.querySelector('input[name="storage_mode"]:checked')?.value || 'individual').toString();
    const availableCodes = getUnusedParasiteCodes();

    for (let tickIndex = 0; tickIndex < tickCount; tickIndex++) {
      const tickNumber = tickIndex + 1;
      const codeSelectorHtml =
        storageMode === 'pool'
          ? `<div class="text-xs text-gray-600">
               <span class="inline-flex items-center gap-2 rounded-md bg-blue-50 px-2 py-1 text-blue-800 border border-blue-200">
                 <i class="fas fa-layer-group"></i>
                 Code auto-assigned
               </span>
             </div>`
          : `<div class="flex flex-col items-center space-y-1">
               <label class="text-xs font-medium text-gray-600">Parasite Code</label>
               <select name="parasite_codes[${sampleId}][${tickIndex}]"
                       class="parasite-code-select w-32 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       required>
                 <option value="">Select code</option>
                 ${availableCodes
                   .map((code, i) => `<option value="${code}" ${i === tickIndex ? 'selected' : ''}>${code}</option>`)
                   .join('')}
               </select>
             </div>`;

      const tickRowHtml = `
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 shadow-sm">
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-medium text-gray-500">Tick</span>
              <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-500 rounded-full">${tickNumber}</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            ${codeSelectorHtml}
          </div>
        </div>
      `;

      $tickRowsContainer.append(tickRowHtml);
    }
  }

  function updateSampleAssignments() {
    const $assignmentList = $('#samples_assignment_list');
    const $assignmentSection = $('#selected_samples_assignment');

    $assignmentList.empty();

    const selectedSamples = [];
    humanSelectize.items.forEach((id) => selectedSamples.push({ id, code: getSampleCode(humanSelectize, id), type: 'Human' }));
    animalSelectize.items.forEach((id) => selectedSamples.push({ id, code: getSampleCode(animalSelectize, id), type: 'Animal' }));
    environmentSelectize.items.forEach((id) =>
      selectedSamples.push({ id, code: getSampleCode(environmentSelectize, id), type: 'Environmental' })
    );

    if (selectedSamples.length === 0) {
      $assignmentSection.hide();
      return;
    }

    $assignmentSection.show();

    selectedSamples.forEach((sample) => {
      const sampleContainerHtml = `
        <div class="sample-container mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-4">
              <div class="flex flex-col">
                <span class="text-sm font-medium text-gray-700">${sample.code}</span>
                <span class="text-xs text-gray-500">(${sample.type})</span>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="flex flex-col items-center space-y-1">
                <label class="text-xs font-medium text-gray-600">Number of Parasites</label>
                <input type="number"
                       name="tick_counts[${sample.id}]"
                       min="1"
                       max="100"
                       value="1"
                       class="tick-count-input w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       data-sample-id="${sample.id}"
                       required>
              </div>
            </div>
          </div>
          <div class="tick-rows-container space-y-2"></div>
        </div>
      `;

      $assignmentList.append(sampleContainerHtml);
      generateTickRows(sample.id, 1);
    });

    updateAvailableCodes();
  }

  $(document).on('change', '.tick-count-input', function () {
    const tickCount = parseInt($(this).val()) || 1;
    const sampleId = $(this).data('sample-id');
    generateTickRows(sampleId, tickCount);
    updateAvailableCodes();
  });

  $(document).on('change', '.parasite-code-select', function () {
    updateAvailableCodes();
  });

  function togglePoolUi() {
    const storageMode = (document.querySelector('input[name="storage_mode"]:checked')?.value || 'individual').toString();
    const poolSection = document.getElementById('pool_code_section');
    const poolCodeSelect = document.getElementById('pool_code');

    if (storageMode === 'pool') {
      poolSection?.classList.remove('hidden');
      if (poolCodeSelect) {
        poolCodeSelect.required = true;
      }
    } else {
      poolSection?.classList.add('hidden');
      if (poolCodeSelect) {
        poolCodeSelect.required = false;
        poolCodeSelect.value = '';
      }
    }

    // Re-render tick rows so code fields appear/disappear
    document.querySelectorAll('.tick-count-input').forEach((el) => {
      const sampleId = el.getAttribute('data-sample-id');
      const tickCount = parseInt(el.value, 10) || 1;
      if (sampleId) {
        generateTickRows(sampleId, tickCount);
      }
    });

    updateAvailableCodes();
  }

  document.querySelectorAll('input[name="storage_mode"]').forEach((el) => {
    el.addEventListener('change', togglePoolUi);
  });

  // Form validation before submission
  $('form').on('submit', function (e) {
    const storageMode = (document.querySelector('input[name="storage_mode"]:checked')?.value || 'individual').toString();
    if (storageMode === 'pool') {
      const poolCode = (document.getElementById('pool_code')?.value || '').toString().trim();
      if (!poolCode) {
        e.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Missing Pool Code',
          text: 'Please select the resulting pool code.',
        });
        return false;
      }

      return true;
    }

    const usedCodes = Array.from(document.querySelectorAll('.parasite-code-select'))
      .map((select) => select.value)
      .filter((code) => code && code !== '');

    const uniqueCodes = [...new Set(usedCodes)];
    if (usedCodes.length !== uniqueCodes.length) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Duplicate Codes Detected',
        text: 'Please ensure each parasite has a unique code. Duplicate codes are not allowed.',
      });
      return false;
    }

    const emptyCodes = Array.from(document.querySelectorAll('.parasite-code-select')).some((select) => !select.value);
    if (emptyCodes) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Missing Codes',
        text: 'Please ensure all parasites have assigned codes.',
      });
      return false;
    }
  });

  // Photo validation/preview is handled by the reusable Blade component.

  updateSampleAssignments();
  togglePoolUi();

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'parasite_species_lookup',
      rowsKey: 'parasiteSpeciesLookupRows',
      selectFieldId: 'parasite_species',
      valueKey: 'name_scientific',
      labelKey: 'name_scientific',
      columns: [
        { key: 'name_scientific', italic: true }, { key: 'name_common' }, { key: 'genus' },
        { key: 'family' }, { key: 'order' }, { key: 'class' }, { key: 'phylum' },
      ],
    });

    window.AlephLookup.register({
      prefix: 'laboratories_lookup',
      rowsKey: 'laboratoryLookupRows',
      selectFieldId: 'parasite_lab',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'lab_type' }, { key: 'country' }, { key: 'city' },
        { key: 'address' }, { key: 'latitude' }, { key: 'longitude' },
      ],
    });
  }
});


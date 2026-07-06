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
  const sortCol = options && options.sortCol !== undefined ? options.sortCol : null;
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
    u.searchParams.set('sort_dir', sortDir === 'desc' ? 'desc' : 'asc');
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
      'w-full rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500';
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

function ensurePageSizeControl(table, onChange) {
  if (!table) return;
  const wrapper = table.closest('.overflow-x-auto') || table.parentElement;
  if (!wrapper) return;

  if (wrapper.querySelector('[data-page-size-control="1"]')) {
    return;
  }

  const row = document.createElement('div');
  row.setAttribute('data-page-size-control', '1');
  row.className = 'flex items-center justify-end gap-2 px-6 py-3 bg-white border-b border-gray-200';

  const label = document.createElement('span');
  label.className = 'text-sm text-gray-600';
  label.textContent = 'Rows per page:';

  const select = document.createElement('select');
  select.className =
    'rounded-md border border-gray-200 px-2 py-1 text-sm text-gray-700 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500';
  [10, 50, 100, 200].forEach((n) => {
    const opt = document.createElement('option');
    opt.value = String(n);
    opt.textContent = String(n);
    select.appendChild(opt);
  });

  select.addEventListener('change', function () {
    if (typeof onChange === 'function') {
      onChange(Number(select.value));
    }
  });

  row.appendChild(label);
  row.appendChild(select);
  wrapper.prepend(row);
}

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function getTubeDisplayLabel(code, alias) {
  const normalizedCode = String(code || '').trim();
  const normalizedAlias = String(alias || '').trim();

  if (window.alephGetTubeBadgeDisplayMode?.() === 'alias' && normalizedAlias !== '') {
    return normalizedAlias;
  }

  return normalizedCode;
}

function buildParasiteDropdownHtml(item, escape) {
  const normalized = normalizeParasiteSelectizeOption(item);
  const primary = window.alephGetTubeDropdownLabel
    ? window.alephGetTubeDropdownLabel(normalized)
    : escape(normalized.code || normalized.text || '');
  const species = String(normalized.species_name || '').trim();
  const speciesHtml = species !== ''
    ? `<span class="ml-2 text-sm text-gray-600"><em>${escape(species)}</em></span>`
    : '';

  return `<div class="py-1 leading-tight"><span class="font-medium">${escape(primary)}</span>${speciesHtml}</div>`;
}

function normalizeParasiteSelectizeOption(item) {
  const normalized = window.alephNormalizeTubeOption ? window.alephNormalizeTubeOption(item) : item;
  if (window.alephGetTubeDropdownLabel) {
    normalized.text = window.alephGetTubeDropdownLabel(normalized);
  }

  return normalized;
}

function seedParasiteSelectizeOptionsFromDom(control, selectElement) {
  if (!control || !selectElement) {
    return;
  }

  Array.from(selectElement.options).forEach(function (nativeOption) {
    if (!nativeOption.value) {
      return;
    }

    const value = String(nativeOption.value);
    const code = (nativeOption.dataset.code || nativeOption.textContent || '').trim();
    const aliasCode = (nativeOption.dataset.aliasCode || '').trim();
    const speciesName = (nativeOption.dataset.speciesName || '').trim();
    const option = normalizeParasiteSelectizeOption({
      value,
      code,
      alias_code: aliasCode,
      species_name: speciesName,
      text: code,
    });

    if (control.options[value]) {
      Object.assign(control.options[value], option);
    } else {
      control.addOption(option);
    }
  });

  control.refreshOptions(false);
}

function captureDissectionAliasValues() {
  const values = Object.assign({}, window.dissectionOldAliasCodes || {});
  $('#dissection_assignment_list input[name^="tube_alias_codes"]').each(function () {
    const name = $(this).attr('name') || '';
    const match = name.match(/tube_alias_codes\[(.+)\]/);
    if (match) {
      values[match[1]] = $(this).val();
    }
  });
  return values;
}

function updateDissectionAssignments() {
  const parasitesControl = $('#parasites_id')[0]?.selectize;
  const sampleTypesControl = $('#parasite_sample_types')[0]?.selectize;
  const $assignmentSection = $('#selected_dissection_assignment');
  const $assignmentList = $('#dissection_assignment_list');
  const $poolSection = $('#pool_tube_alias_section');
  const storageMode = $('input[name="storage_mode"]:checked').val() || 'individual';
  const autoFromParent = $('#auto_alias_from_parent').is(':checked');
  const existingAliases = captureDissectionAliasValues();

  $assignmentList.empty();

  if (!parasitesControl || parasitesControl.items.length === 0) {
    $assignmentSection.hide();
    return;
  }

  $assignmentSection.show();

  if (storageMode === 'pool') {
    $poolSection.removeClass('hidden');
    $assignmentList.closest('.bg-gray-50').addClass('hidden');
    $('#selected_dissection_assignment > .flex').first().addClass('hidden');

    if ($('#auto_alias_from_parent').is(':checked')) {
      const firstParasiteId = parasitesControl.items[0];
      const firstOpt = firstParasiteId ? parasitesControl.options[String(firstParasiteId)] : null;
      const parentAlias = firstOpt ? String(firstOpt.alias_code || '').trim() : '';
      if (parentAlias !== '') {
        $('#pool_tube_alias').val(parentAlias);
      }
    }

    return;
  }

  $poolSection.addClass('hidden');
  $assignmentList.closest('.bg-gray-50').removeClass('hidden');
  $('#selected_dissection_assignment > .flex').first().removeClass('hidden');

  const sampleTypes = sampleTypesControl ? sampleTypesControl.items.map(String) : [];
  if (sampleTypes.length === 0) {
    return;
  }

  parasitesControl.items.forEach(function (parasiteId) {
    const key = String(parasiteId);
    const opt = parasitesControl.options[key] || {};
    const parasiteCode = String(opt.code || opt.text || key);
    const parasiteAlias = String(opt.alias_code || '').trim();

    sampleTypes.forEach(function (typeName) {
      const aliasKey = `${key}|${typeName}`;
      let prefilledAlias = '';

      if (autoFromParent) {
        prefilledAlias = parasiteAlias;
      } else if (existingAliases[aliasKey] !== undefined) {
        prefilledAlias = String(existingAliases[aliasKey] || '');
      }

      const rowHtml = `
        <tr class="bg-white">
          <td class="py-3 pr-4 font-medium text-gray-800">${escapeHtml(parasiteCode)}</td>
          <td class="py-3 pr-4 text-gray-600">${parasiteAlias ? escapeHtml(parasiteAlias) : '—'}</td>
          <td class="py-3 pr-4 text-gray-700">${escapeHtml(typeName)}</td>
          <td class="py-3">
            <input type="text" name="tube_alias_codes[${escapeHtml(aliasKey)}]" value="${escapeHtml(prefilledAlias)}"
              class="w-full min-w-[160px] rounded border border-gray-300 px-2 py-1 text-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="Output tube alias (optional)">
          </td>
        </tr>
      `;

      $assignmentList.append(rowHtml);
    });
  });
}

$(document).ready(function () {
  const aliasColumnIndex = 2;

  if ($('#parasites_id').length) {
    $('#parasites_id').selectize({
      placeholder: 'Enter parasite code, alias, or species',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
      maxOptions: 20,
      openOnFocus: true,
      valueField: 'value',
      labelField: 'text',
      searchField: ['text', 'code', 'alias_code', 'species_name'],
      preload: false,
      render: {
        option: function (item, escape) {
          return buildParasiteDropdownHtml(item, escape);
        },
        item: function (item, escape) {
          const normalized = normalizeParasiteSelectizeOption(item);
          if (window.alephBuildTubeBadgeItemHtml) {
            const badge = window.alephBuildTubeBadgeItemHtml(normalized);
            const species = String(normalized.species_name || '').trim();
            const speciesHtml = species !== ''
              ? ` <em class="text-white/90 not-italic font-normal">· <span class="italic">${escape(species)}</span></em>`
              : '';

            return `<div>${badge}${speciesHtml}</div>`;
          }

          return buildParasiteDropdownHtml(item, escape);
        },
      },
      onInitialize: function () {
        seedParasiteSelectizeOptionsFromDom(this, this.$input[0]);
        window.alephConfigureTubeBadgeSelectize?.(this.$input[0]);
      },
      load: function (query, callback) {
        if (!query.length) {
          return callback();
        }
        $.ajax({
          url: '/samples/parasites/dissection/parasites/search',
          type: 'GET',
          dataType: 'json',
          data: { q: query },
          error: function () {
            callback();
          },
          success: function (res) {
            const normalized = Array.isArray(res)
              ? res.map(function (item) {
                  return normalizeParasiteSelectizeOption(item);
                })
              : res;
            callback(normalized);
          },
        });
      },
    });
  }

  if ($('#parasite_sample_types').length) {
    $('#parasite_sample_types').selectize({
      placeholder: 'Search or enter parasite sample type',
      create: true,
      dropdownParent: 'body',
      plugins: ['remove_button'],
      onChange: updateDissectionAssignments,
    });
  }

  if ($('#laboratory').length) {
    $('#laboratory').selectize({
      placeholder: 'Search or enter laboratory',
      create: true,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }

  if ($('#people_id').length) {
    $('#people_id').selectize({
      placeholder: 'Select person',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }

  const dissectionModalState = {
    baseUrl: '/samples/parasites/dissection/parasites',
    currentUrl: '/samples/parasites/dissection/parasites',
    currentFilters: {},
    perPage: 50,
    sortCol: null,
    sortDir: 'asc',
    pendingSelectedIds: new Set(),
    pendingLabelsById: {},
    pendingAliasesById: {},
    pendingSpeciesById: {},
    filterDebounce: null,
    lastFilterFocus: null,
  };

  const $modal = $('#parasites_modal');
  const $modalContent = $modal.find('[data-modal-content]').first();
  const selectizeControl = $('#parasites_id')[0]?.selectize;

  if (!selectizeControl) {
    return;
  }

  function getAliasFromRow(checkbox) {
    const raw = checkbox.closest('tr').children().eq(aliasColumnIndex).text().trim();
    if (!raw || raw.toUpperCase() === 'N/A') {
      return '';
    }

    return raw;
  }

  function syncPendingFromSelectize() {
    dissectionModalState.pendingSelectedIds = new Set(selectizeControl.items.map(String));
    dissectionModalState.pendingLabelsById = {};
    dissectionModalState.pendingAliasesById = {};
    selectizeControl.items.forEach(function (id) {
      const key = String(id);
      const opt = selectizeControl.options[key];
      if (opt && opt.text) {
        dissectionModalState.pendingLabelsById[key] = String(opt.code || opt.text);
        if (opt.alias_code) {
          dissectionModalState.pendingAliasesById[key] = String(opt.alias_code);
        }
      }
    });
  }

  function updateSelectedCount() {
    const count = selectizeControl.items.length;
    $('#parasites_id_count').text(`(${count} selected)`);
    window.alephRefreshTubeBadgeDisplay?.('parasites_id');
    updateDissectionAssignments();
  }

  function syncCheckboxesWithPending() {
    const pending = dissectionModalState.pendingSelectedIds;
    const $checkboxes = $modal.find('.select-parasite');
    $checkboxes.each(function () {
      const id = String($(this).val());
      $(this).prop('checked', pending.has(id));
    });
    const allChecked = $checkboxes.length > 0 && $checkboxes.length === $modal.find('.select-parasite:checked').length;
    $modal.find('#select_all_parasites').prop('checked', allChecked);
    $modal.find('#selected_count').text(String(pending.size));
  }

  function loadModalContent(url, opts) {
    const preservePage = Boolean(opts && opts.preservePage);
    const resolved = resolveModalUrl(url, dissectionModalState.currentUrl || dissectionModalState.baseUrl);
    const normalizedUrl = resolved ? resolved.pathname + (resolved.search || '') : url;

    const finalUrl = buildModalUrlWithFilters(
      normalizedUrl,
      dissectionModalState.currentFilters,
      dissectionModalState.baseUrl,
      {
        preservePage,
        perPage: dissectionModalState.perPage,
        sortCol: dissectionModalState.sortCol,
        sortDir: dissectionModalState.sortDir,
      }
    );
    dissectionModalState.currentUrl = finalUrl;

    const silent = Boolean(opts && opts.silent);
    if (!silent) {
      $modalContent.html('<div class="text-sm text-gray-500">Loading…</div>');
    }
    $.ajax({
      url: finalUrl,
      type: 'GET',
      dataType: 'html',
      success: function (html) {
        $modalContent.html(html);
        const table = $modalContent[0].querySelector('table');
        if (table) {
          ensureSortableHeaders(table);
          ensureColumnFilters(table, dissectionModalState.currentFilters, function (col, value, inputEl) {
            dissectionModalState.lastFilterFocus = {
              colIndex: col,
              caretStart: inputEl && typeof inputEl.selectionStart === 'number' ? inputEl.selectionStart : null,
              caretEnd: inputEl && typeof inputEl.selectionEnd === 'number' ? inputEl.selectionEnd : null,
            };
            const v = (value || '').toString();
            if (v.trim() === '') {
              delete dissectionModalState.currentFilters[col];
            } else {
              dissectionModalState.currentFilters[col] = v;
            }
            if (dissectionModalState.filterDebounce) {
              clearTimeout(dissectionModalState.filterDebounce);
            }
            dissectionModalState.filterDebounce = setTimeout(function () {
              loadModalContent(dissectionModalState.baseUrl, { preservePage: false, silent: true });
            }, 500);
          });
          ensurePageSizeControl(table, function (newPerPage) {
            dissectionModalState.perPage = newPerPage;
            loadModalContent(dissectionModalState.baseUrl, { preservePage: false });
          });
          const pageSelect = $modalContent[0].querySelector('[data-page-size-control="1"] select');
          if (pageSelect) {
            pageSelect.value = String(dissectionModalState.perPage);
          }

          const focusState = dissectionModalState.lastFilterFocus;
          if (focusState && Number.isInteger(focusState.colIndex)) {
            const focusInput = table.querySelector(`input[data-filter-col="${focusState.colIndex}"]`);
            if (focusInput) {
              focusInput.focus();
              const valueLength = focusInput.value.length;
              const caretStart = focusState.caretStart;
              const caretEnd = focusState.caretEnd;
              if (typeof caretStart === 'number' && typeof caretEnd === 'number') {
                focusInput.setSelectionRange(
                  Math.min(Math.max(0, caretStart), valueLength),
                  Math.min(Math.max(0, caretEnd), valueLength)
                );
              } else {
                focusInput.setSelectionRange(valueLength, valueLength);
              }
            }
          }
        }
        syncCheckboxesWithPending();
      },
      error: function () {
        $modalContent.html('<div class="text-sm text-red-600">Failed to load data.</div>');
      },
    });
  }

  $('#parasites_btn').on('click', function () {
    $modal.removeClass('hidden');
    dissectionModalState.currentFilters = {};
    dissectionModalState.currentUrl = dissectionModalState.baseUrl;
    dissectionModalState.perPage = 50;
    dissectionModalState.sortCol = null;
    dissectionModalState.sortDir = 'asc';
    syncPendingFromSelectize();
    loadModalContent(dissectionModalState.baseUrl, { preservePage: false });
  });

  $('#parasites_close_btn').on('click', function () {
    $modal.addClass('hidden');
  });

  $(document).on('click', '#parasites_modal #parasites_cancel_btn', function () {
    $modal.addClass('hidden');
  });

  $(document).on('click', '#parasites_modal #confirm_parasite_selection', function (e) {
    e.preventDefault();
    selectizeControl.clear(true);
    Array.from(dissectionModalState.pendingSelectedIds).forEach(function (id) {
      const label = dissectionModalState.pendingLabelsById[String(id)] || (selectizeControl.options[id] ? (selectizeControl.options[id].code || selectizeControl.options[id].text) : String(id));
      const alias = dissectionModalState.pendingAliasesById[String(id)] || (selectizeControl.options[id] ? selectizeControl.options[id].alias_code : '');
      const option = normalizeParasiteSelectizeOption({
        value: id,
        code: label,
        alias_code: alias,
        text: label,
      });

      if (!selectizeControl.options[id]) {
        selectizeControl.addOption(option);
      } else {
        Object.assign(selectizeControl.options[id], option);
      }
      selectizeControl.addItem(id);

      const nativeOption = selectizeControl.$input[0].querySelector(`option[value="${id}"]`);
      if (nativeOption) {
        nativeOption.dataset.code = label;
        nativeOption.dataset.aliasCode = alias;
        nativeOption.textContent = option.text;
      }
    });
    window.alephConfigureTubeBadgeSelectize?.('parasites_id');
    updateSelectedCount();
    $modal.addClass('hidden');
  });

  $(document).on('change', '#parasites_modal #select_all_parasites', function () {
    const isChecked = $(this).is(':checked');
    $modal.find('.select-parasite').each(function () {
      const id = String($(this).val());
      $(this).prop('checked', isChecked);
      if (isChecked) {
        dissectionModalState.pendingSelectedIds.add(id);
        if (!dissectionModalState.pendingLabelsById[id]) {
          dissectionModalState.pendingLabelsById[id] = $(this).closest('tr').children().eq(1).text().trim();
        }
        dissectionModalState.pendingAliasesById[id] = getAliasFromRow($(this));
      } else {
        dissectionModalState.pendingSelectedIds.delete(id);
        delete dissectionModalState.pendingAliasesById[id];
      }
    });
    syncCheckboxesWithPending();
  });

  $(document).on('change', '#parasites_modal .select-parasite', function () {
    const id = String($(this).val());
    const isChecked = $(this).is(':checked');
    if (isChecked) {
      dissectionModalState.pendingSelectedIds.add(id);
      if (!dissectionModalState.pendingLabelsById[id]) {
        dissectionModalState.pendingLabelsById[id] = $(this).closest('tr').children().eq(1).text().trim();
      }
      dissectionModalState.pendingAliasesById[id] = getAliasFromRow($(this));
    } else {
      dissectionModalState.pendingSelectedIds.delete(id);
      delete dissectionModalState.pendingAliasesById[id];
    }
    syncCheckboxesWithPending();
  });

  $(document).on('click', '#parasites_modal nav a[href]', function (e) {
    e.preventDefault();
    const href = $(this).attr('href');
    if (!href) return;
    loadModalContent(href, { preservePage: true });
  });

  $(document).on('click', '#parasites_modal table thead tr:first-child th', function () {
    if ($(this).find('input[type="checkbox"]').length) {
      return;
    }

    const colIndex = $(this).index();
    if (dissectionModalState.sortCol === colIndex) {
      dissectionModalState.sortDir = dissectionModalState.sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      dissectionModalState.sortCol = colIndex;
      dissectionModalState.sortDir = 'asc';
    }

    loadModalContent(dissectionModalState.baseUrl, { preservePage: false });
  });

  function getCsrfToken() {
    return (
      $('meta[name="csrf-token"]').attr('content') ||
      document.querySelector('input[name="_token"]')?.value ||
      ''
    );
  }

  $(document).on('change', '#parasites_modal .parasite-status-select', function () {
    const $select = $(this);
    const parasiteId = String($select.data('parasite-id') || '');
    const status = String($select.val() || '');
    const originalStatus = String($select.data('original-status') || 'intact');

    if (!parasiteId || !status) {
      return;
    }

    $select.prop('disabled', true);

    $.ajax({
      url: `/samples/parasites/${parasiteId}/status`,
      type: 'PATCH',
      dataType: 'json',
      contentType: 'application/json',
      data: JSON.stringify({ status }),
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        Accept: 'application/json',
      },
      success: function () {
        $select.data('original-status', status);
      },
      error: function (xhr) {
        $select.val(originalStatus);
        const message = xhr?.responseJSON?.message || 'Failed to update parasite status.';
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: message, confirmButtonColor: '#d33' });
        } else {
          alert(message);
        }
      },
      complete: function () {
        $select.prop('disabled', false);
      },
    });
  });

  selectizeControl.on('item_add', updateSelectedCount);
  selectizeControl.on('item_remove', updateSelectedCount);

  $('input[name="storage_mode"]').on('change', updateDissectionAssignments);
  $('#auto_alias_from_parent').on('change', updateDissectionAssignments);

  updateSelectedCount();
  window.alephConfigureTubeBadgeSelectize?.('parasites_id');

  const successMessageElement = document.getElementById('successMessage');
  const errorMessageElement = document.getElementById('errorMessage');
  if (successMessageElement) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent, timer: 2500, showConfirmButton: false });
    } else {
      alert(successMessageElement.textContent);
    }
  }
  if (errorMessageElement) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent, confirmButtonColor: '#d33' });
    } else {
      alert(errorMessageElement.textContent);
    }
  }

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'laboratories_lookup',
      rowsKey: 'laboratoryLookupRows',
      selectFieldId: 'laboratory',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'lab_type' }, { key: 'country' }, { key: 'city' },
        { key: 'address' }, { key: 'latitude' }, { key: 'longitude' },
      ],
    });
  }
});

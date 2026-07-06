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
    input.value = (initialFilters && initialFilters[index]) ? String(initialFilters[index]) : '';
    input.className = 'w-full rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
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
  });
}

$(document).ready(function () {
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
    });
  }

  initAjaxSelectize('human_tube_id', 'Enter human tube code', '/samples/microplastics/create/tubes/human/search');
  initAjaxSelectize('animal_tube_id', 'Enter animal tube code', '/samples/microplastics/create/tubes/animal/search');
  initAjaxSelectize('environment_tube_id', 'Enter environmental tube code', '/samples/microplastics/create/tubes/environment/search');
  initAjaxSelectize('parasite_tube_id', 'Enter parasite tube code', '/samples/microplastics/create/tubes/parasite/search');
  initAjaxSelectize('pool_tube_id', 'Enter pool tube code', '/samples/microplastics/create/tubes/pool/search');

  $('#mps_type').selectize({ create: false, dropdownParent: 'body', plugins: ['remove_button'] });
  $('#protocol').selectize({ create: false, dropdownParent: 'body', plugins: ['remove_button'] });
  $('#lab').selectize({ create: false, dropdownParent: 'body', plugins: ['remove_button'] });
  $('#scientist').selectize({ create: false, dropdownParent: 'body', plugins: ['remove_button'] });

  if ($('#sub_project_id').length) {
    $('#sub_project_id').selectize({
      placeholder: 'Select sub-project',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }

  $('#microplastics_lab_form_btn').on('click', function () {
    $('#microplastics_lab_form_modal').removeClass('hidden');
  });

  $('#microplastics_lab_form_close_btn').on('click', function () {
    $('#microplastics_lab_form_modal').addClass('hidden');
  });

  $('#microplastics_protocol_form_btn').on('click', function () {
    $('#microplastics_protocol_form_modal').removeClass('hidden');
  });

  $('#microplastics_protocol_form_close_btn').on('click', function () {
    $('#microplastics_protocol_form_modal').addClass('hidden');
  });

  function toggleFields(selectedValue) {
    ['human_model', 'animal_model', 'environment_model', 'parasite_model', 'pool_model']
      .forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
          el.style.display = 'none';
        }
      });

    const map = {
      'Human samples': 'human_model',
      'Animal samples': 'animal_model',
      'Environmental samples': 'environment_model',
      'Parasite samples': 'parasite_model',
      'Pools': 'pool_model',
    };

    const target = document.getElementById(map[selectedValue] || '');
    if (target) {
      target.style.display = 'block';
    }
  }

  function setupTubeSelector({ selectId, modalId, openBtnId, closeBtnId, confirmBtnId, checkboxClass, masterCheckboxId, fetchUrl, aliasColumnIndex = 2 }) {
    const $select = $(`#${selectId}`);
    const selectizeControl = $select[0].selectize;
    const $modal = $(`#${modalId}`);
    const $modalContent = $modal.find('[data-modal-content]').first();
    const baseUrl = fetchUrl;
    let currentUrl = fetchUrl;
    let currentFilters = {};
    let filterDebounce = null;
    let perPage = 50;
    let sortCol = null;
    let sortDir = 'asc';

    let pendingSelectedIds = new Set();
    let pendingLabelsById = {};
    let pendingAliasesById = {};

    function updateTubeCount() {
      const count = selectizeControl.items.length;
      $(`#${selectId}_count`).text(`(${count} selected)`);
      window.alephRefreshTubeBadgeDisplay?.(selectId);
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
          pendingAliasesById[key] = String(opt.alias_code || '');
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

    function syncCheckboxesWithPending() {
      $(`#${modalId} .${checkboxClass}`).each(function () {
        const checkbox = $(this);
        checkbox.prop('checked', pendingSelectedIds.has(String(checkbox.val())));
      });

      const allChecked = $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
      $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);
    }

    function loadModalContent(url, options) {
      const silent = Boolean(options && options.silent);
      if (!silent) {
        $modalContent.html('<div class="text-sm text-gray-500">Loading…</div>');
      }
      const resolved = resolveModalUrl(url, currentUrl || baseUrl);
      const normalizedUrl = resolved ? resolved.pathname + (resolved.search || '') : url;
      const requestUrl = buildModalUrlWithFilters(normalizedUrl, currentFilters, baseUrl, {
        preservePage: Boolean(options && options.preservePage),
        perPage,
        sortCol,
        sortDir,
      });
      currentUrl = requestUrl;

      $.get(requestUrl).done(function (html) {
        $modalContent.html(html);
        const table = $modalContent.get(0).querySelector('table');
        if (table) {
          ensureSortableHeaders(table);
          ensureColumnFilters(table, currentFilters, function (colIndex, value) {
            if ((value || '').toString().trim() === '') {
              delete currentFilters[colIndex];
            } else {
              currentFilters[colIndex] = value;
            }
            if (filterDebounce) {
              clearTimeout(filterDebounce);
            }
            filterDebounce = setTimeout(function () {
              loadModalContent(baseUrl, { preservePage: false, silent: true });
            }, 500);
          });
        }
        syncCheckboxesWithPending();
      }).fail(function () {
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

    $(document).on('click', `#${modalId} [id$="_cancel_btn"]`, function () {
      $(`#${modalId}`).hide();
    });

    $(document).on('click', `#${modalId} #${confirmBtnId}`, function (e) {
      e.preventDefault();
      selectizeControl.clear(true);

      Array.from(pendingSelectedIds).forEach(function (id) {
        const code = pendingLabelsById[id] || id;
        const alias = pendingAliasesById[id] || '';
        const displayLabel = window.alephGetTubeBadgeLabel
          ? window.alephGetTubeBadgeLabel({ code, alias_code: alias })
          : code;

        if (!selectizeControl.options[id]) {
          selectizeControl.addOption({ value: id, text: displayLabel, code, alias_code: alias });
        }

        selectizeControl.addItem(id);

        const nativeOption = selectizeControl.$input[0].querySelector(`option[value="${id}"]`);
        if (nativeOption) {
          nativeOption.dataset.code = code;
          nativeOption.dataset.aliasCode = alias;
          nativeOption.textContent = displayLabel;
        }
      });

      updateTubeCount();
      $(`#${modalId}`).hide();
    });

    selectizeControl.on('item_remove', updateTubeCount);
    selectizeControl.on('item_add', updateTubeCount);

    $(document).on('change', `#${modalId} #${masterCheckboxId}`, function () {
      const checked = $(this).is(':checked');
      $(`#${modalId} .${checkboxClass}`).each(function () {
        const checkbox = $(this);
        const id = String(checkbox.val());
        checkbox.prop('checked', checked);
        if (checked) {
          pendingSelectedIds.add(id);
          pendingLabelsById[id] = checkbox.closest('tr').children().eq(1).text().trim();
          pendingAliasesById[id] = getAliasFromRow(checkbox);
        } else {
          pendingSelectedIds.delete(id);
          delete pendingLabelsById[id];
          delete pendingAliasesById[id];
        }
      });
    });

    $(document).on('change', `#${modalId} .${checkboxClass}`, function () {
      const checkbox = $(this);
      const id = String(checkbox.val());
      if (checkbox.is(':checked')) {
        pendingSelectedIds.add(id);
        pendingLabelsById[id] = checkbox.closest('tr').children().eq(1).text().trim();
        pendingAliasesById[id] = getAliasFromRow(checkbox);
      } else {
        pendingSelectedIds.delete(id);
        delete pendingLabelsById[id];
        delete pendingAliasesById[id];
      }
    });

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
      if (url) {
        loadModalContent(url, { preservePage: true });
      }
    });

    updateTubeCount();
  }

  setupTubeSelector({ selectId: 'human_tube_id', modalId: 'human_tubes_modal', openBtnId: 'human_tubes_btn', closeBtnId: 'human_tubes_close_btn', confirmBtnId: 'confirm_human_tube_selection', checkboxClass: 'select-human-tube', masterCheckboxId: 'select_all_human_tubes', fetchUrl: '/samples/microplastics/create/tubes/human' });
  setupTubeSelector({ selectId: 'animal_tube_id', modalId: 'animal_tubes_modal', openBtnId: 'animal_tubes_btn', closeBtnId: 'animal_tubes_close_btn', confirmBtnId: 'confirm_tube_selection', checkboxClass: 'select-tube', masterCheckboxId: 'select_all_tubes', fetchUrl: '/samples/microplastics/create/tubes/animal' });
  setupTubeSelector({ selectId: 'environment_tube_id', modalId: 'environment_tubes_modal', openBtnId: 'environment_tubes_btn', closeBtnId: 'environment_tubes_close_btn', confirmBtnId: 'confirm_environment_tube_selection', checkboxClass: 'select-environment-tube', masterCheckboxId: 'select_all_environment_tubes', fetchUrl: '/samples/microplastics/create/tubes/environment' });
  setupTubeSelector({ selectId: 'parasite_tube_id', modalId: 'parasite_tubes_modal', openBtnId: 'parasite_tubes_btn', closeBtnId: 'parasite_tubes_close_btn', confirmBtnId: 'confirm_parasite_tube_selection', checkboxClass: 'select-parasite-tube', masterCheckboxId: 'select_all_parasite_tubes', fetchUrl: '/samples/microplastics/create/tubes/parasite' });
  setupTubeSelector({ selectId: 'pool_tube_id', modalId: 'pool_tubes_modal', openBtnId: 'pool_tubes_btn', closeBtnId: 'pool_tubes_close_btn', confirmBtnId: 'confirm_pool_tube_selection', checkboxClass: 'select-pool-tube', masterCheckboxId: 'select_all_pool_tubes', fetchUrl: '/samples/microplastics/create/tubes/pool' });

  const initialValue = $('#model').val();
  toggleFields(initialValue);
  window.alephRefreshAllTubeBadgeDisplays?.();

  const successMessageElement = document.getElementById('successMessage');
  const errorMessageElement = document.getElementById('errorMessage');

  if (successMessageElement) {
    Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent });
  }

  if (errorMessageElement) {
    Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent });
  }
});

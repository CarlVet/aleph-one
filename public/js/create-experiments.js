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

$(document).ready(function () {

  function setExperimentProtocolValue(protocolName) {
    const normalizedName = String(protocolName || '').trim();
    const protocolElement = document.getElementById('protocol');
    if (!normalizedName || !protocolElement) {
      return;
    }

    if (protocolElement.selectize) {
      const selectize = protocolElement.selectize;
      const existingOption = selectize.options[normalizedName];
      if (!existingOption) {
        selectize.addOption({ value: normalizedName, text: normalizedName });
      }
      selectize.setValue(normalizedName, true);
    } else {
      protocolElement.value = normalizedName;
      protocolElement.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function setExperimentLaboratoryValue(laboratoryName) {
    const normalizedName = String(laboratoryName || '').trim();
    const laboratoryElement = document.getElementById('lab');
    if (!normalizedName || !laboratoryElement) {
      return;
    }

    if (laboratoryElement.selectize) {
      const selectize = laboratoryElement.selectize;
      const existingOption = selectize.options[normalizedName];
      if (!existingOption) {
        selectize.addOption({ value: normalizedName, text: normalizedName });
      }
      selectize.setValue(normalizedName, true);
    } else {
      laboratoryElement.value = normalizedName;
      laboratoryElement.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function escapeLookupHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function populateLookupTableRows(tableId, rowsType, rowBuilder) {
    const table = document.getElementById(tableId);
    const tbody = table ? table.querySelector(`tbody[data-lookup-rows="${rowsType}"]`) : null;
    if (!tbody || tbody.dataset.rowsRendered === '1') {
      return;
    }

    const emptyRow = tbody.querySelector('tr[id$="_empty_state"]');
    const rows = rowsType === 'protocols'
      ? (Array.isArray(window.protocolLookupRows) ? window.protocolLookupRows : [])
      : (Array.isArray(window.laboratoryLookupRows) ? window.laboratoryLookupRows : []);

    rows.forEach(function (row) {
      tbody.insertAdjacentHTML('beforeend', rowBuilder(row));
    });

    if (emptyRow) {
      tbody.appendChild(emptyRow);
    }

    tbody.dataset.rowsRendered = '1';
  }

  function buildProtocolLookupRow(protocol) {
    const code = String(protocol && protocol.code ? protocol.code : '').trim();
    const name = String(protocol && protocol.name ? protocol.name : '').trim();
    const techniqueName = String(protocol && protocol.technique_name ? protocol.technique_name : '').trim();
    const techniqueType = String(protocol && protocol.technique_type ? protocol.technique_type : '').trim();
    const targetPathogens = Array.isArray(protocol && protocol.target_pathogens) ? protocol.target_pathogens.filter(Boolean) : [];
    const projectCodes = Array.isArray(protocol && protocol.project_codes) ? protocol.project_codes.filter(Boolean) : [];
    const projectLinks = projectCodes.length
      ? projectCodes.map((projectCode) => `<a href="/projects/${encodeURIComponent(projectCode)}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${escapeLookupHtml(projectCode)}</a>`).join('')
      : 'N/A';

    return `
      <tr class="protocol-lookup-row hover:bg-gray-50"
          data-code="${escapeLookupHtml((code || 'N/A').toLowerCase())}"
          data-name="${escapeLookupHtml((name || 'N/A').toLowerCase())}"
          data-technique-name="${escapeLookupHtml((techniqueName || 'N/A').toLowerCase())}"
          data-technique-type="${escapeLookupHtml((techniqueType || 'N/A').toLowerCase())}"
          data-pathogens="${escapeLookupHtml((targetPathogens.length ? targetPathogens.join(', ') : 'N/A').toLowerCase())}"
          data-projects="${escapeLookupHtml((projectCodes.length ? projectCodes.join(', ') : 'N/A').toLowerCase())}">
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(code || 'N/A')}</td>
        <td class="px-4 py-3">
          ${code
            ? `<a href="/protocols/${encodeURIComponent(code)}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">${escapeLookupHtml(name || 'N/A')}</a>`
            : `<span class="font-medium text-gray-900">${escapeLookupHtml(name || 'N/A')}</span>`}
        </td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(techniqueName || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(techniqueType || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(targetPathogens.length ? targetPathogens.join(', ') : 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${projectCodes.length ? `<div class="flex flex-wrap gap-2">${projectLinks}</div>` : 'N/A'}</td>
        <td class="px-4 py-3">
          <button type="button" class="protocol-table-select inline-flex items-center rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700" data-protocol-name="${escapeLookupHtml(name)}">
            Select
          </button>
        </td>
      </tr>
    `;
  }

  function buildLaboratoryLookupRow(laboratory) {
    const name = String(laboratory && laboratory.name ? laboratory.name : '').trim();
    const labType = String(laboratory && laboratory.lab_type ? laboratory.lab_type : '').trim();
    const country = String(laboratory && laboratory.country ? laboratory.country : '').trim();
    const city = String(laboratory && laboratory.city ? laboratory.city : '').trim();
    const address = String(laboratory && laboratory.address ? laboratory.address : '').trim();
    const latitude = laboratory && laboratory.latitude !== null && laboratory.latitude !== undefined
      ? String(laboratory.latitude)
      : '';
    const longitude = laboratory && laboratory.longitude !== null && laboratory.longitude !== undefined
      ? String(laboratory.longitude)
      : '';

    return `
      <tr class="laboratory-lookup-row hover:bg-gray-50"
          data-name="${escapeLookupHtml((name || 'N/A').toLowerCase())}"
          data-lab-type="${escapeLookupHtml((labType || 'N/A').toLowerCase())}"
          data-country="${escapeLookupHtml((country || 'N/A').toLowerCase())}"
          data-city="${escapeLookupHtml((city || 'N/A').toLowerCase())}"
          data-address="${escapeLookupHtml((address || 'N/A').toLowerCase())}"
          data-latitude="${escapeLookupHtml((latitude || 'N/A').toLowerCase())}"
          data-longitude="${escapeLookupHtml((longitude || 'N/A').toLowerCase())}">
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(name || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(labType || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(country || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(city || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(address || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(latitude || 'N/A')}</td>
        <td class="px-4 py-3 text-gray-700">${escapeLookupHtml(longitude || 'N/A')}</td>
        <td class="px-4 py-3">
          <button type="button" class="laboratory-table-select inline-flex items-center rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700" data-laboratory-name="${escapeLookupHtml(name)}">
            Select
          </button>
        </td>
      </tr>
    `;
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

    function datasetKey(key) {
      return String(key || '').replace(/-([a-z])/g, function (_, char) {
        return char.toUpperCase();
      });
    }

    function rowValue(row, key) {
      const lookupKey = datasetKey(key);
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

    function renderProtocolLookupTable() {
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
      input.addEventListener('input', function () {
        renderProtocolLookupTable();
      });
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

        renderProtocolLookupTable();
      });
    });

    renderProtocolLookupTable();
    table.dataset.lookupInitialized = '1';
  }

  function initProtocolLookupTable() {
    populateLookupTableRows('protocol_lookup_table', 'protocols', buildProtocolLookupRow);
    initLookupTable({
      tableId: 'protocol_lookup_table',
      rowSelector: '.protocol-lookup-row',
      filterSelector: '.protocol-lookup-filter',
      sortSelector: '.protocol-lookup-sort',
      emptyStateId: 'protocol_lookup_empty_state',
    });
  }

  function initLaboratoryLookupTable() {
    populateLookupTableRows('laboratory_lookup_table', 'laboratories', buildLaboratoryLookupRow);
    initLookupTable({
      tableId: 'laboratory_lookup_table',
      rowSelector: '.laboratory-lookup-row',
      filterSelector: '.laboratory-lookup-filter',
      sortSelector: '.laboratory-lookup-sort',
      emptyStateId: 'laboratory_lookup_empty_state',
    });
  }

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

  function initTubeSelectize(selectId, placeholder, searchUrl) {
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
          }
        });
      }
    });
  }

  initTubeSelectize('human_tube_id', 'Enter human tube', '/experiments/create/tubes/human/search');
  initTubeSelectize('animal_tube_id', 'Enter animal tube', '/experiments/create/tubes/animal/search');
  initTubeSelectize('environment_tube_id', 'Enter environmental tube', '/experiments/create/tubes/environment/search');
  initTubeSelectize('parasite_tube_id', 'Enter parasite tube', '/experiments/create/tubes/parasite/search');
  initTubeSelectize('nucleic_tube_id', 'Enter nucleic tube', '/experiments/create/tubes/nucleic/search');
  initTubeSelectize('culture_tube_id', 'Enter culture tube', '/experiments/create/tubes/culture/search');
  initTubeSelectize('pool_tube_id', 'Enter pool tube', '/experiments/create/tubes/pool/search');

  $('#protocol').selectize({
    placeholder: "Search and select protocol",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#pathogen').selectize({
    placeholder: "Search and select pathogen",
    create: false,
    dropdownParent: 'body',
  });

  $('#outcome_qual').selectize({
    placeholder: "Select qualitative outcome",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  $('#lab').selectize({
    placeholder: "Search or enter laboratory",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button'],
  });

  $('#scientist').selectize({
    placeholder: "Select qualitative outcome",
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

  function getSelectedTubeContext() {
    const model = (document.getElementById('model')?.value || '').trim();

    const map = {
      'Human samples': 'human_tube_id',
      'Animal samples': 'animal_tube_id',
      'Environmental samples': 'environment_tube_id',
      'Parasite samples': 'parasite_tube_id',
      'Nucleic acids': 'nucleic_tube_id',
      'Cultures': 'culture_tube_id',
      'Pools': 'pool_tube_id',
    };

    const selectId = map[model];
    if (!selectId) {
      return { model, tubeIds: [] };
    }

    const el = document.getElementById(selectId);
    const selectize = el && el.selectize ? el.selectize : null;
    const tubeIds = selectize ? selectize.items.map(String) : [];

    return { model, tubeIds };
  }

  function protocolTechniqueLabel(protocolName) {
    const name = String(protocolName || '').trim();
    if (!name || !Array.isArray(window.protocolsList)) return '';

    const protocol = window.protocolsList.find((p) => p && String(p.name || '').trim() === name);
    if (!protocol) return '';

    const technique = protocol.techniques || null;
    const label = (technique && (technique.type || technique.name)) || '';
    return String(label || '').trim();
  }

  const $warning = $('#experiment_suitability_warning');
  const $warningList = $('#experiment_suitability_warning_list');
  const $ack = $('#experiment_suitability_ack');
  const $override = $('#suitability_override');

  function clearWarning() {
    $warning.addClass('hidden').attr('data-has-warning', '0');
    $warningList.empty();
    $ack.prop('checked', false);
    $override.val('0');
  }

  function showWarning(warnings) {
    const list = Array.isArray(warnings) ? warnings : [];
    if (list.length === 0) {
      clearWarning();
      return;
    }

    $warning.removeClass('hidden').attr('data-has-warning', '1');
    $warningList.empty();
    list.forEach((w) => {
      const li = document.createElement('li');
      li.textContent = String(w);
      $warningList.append(li);
    });
  }

  $ack.on('change', function () {
    $override.val($ack.is(':checked') ? '1' : '0');
  });

  let suitabilityDebounce = null;
  function scheduleSuitabilityCheck() {
    if (suitabilityDebounce) {
      clearTimeout(suitabilityDebounce);
    }

    suitabilityDebounce = setTimeout(runSuitabilityCheck, 250);
  }

  function runSuitabilityCheck() {
    const protocolName = (document.getElementById('protocol')?.value || '').trim();
    if (!protocolName) {
      clearWarning();
      return;
    }

    const techniqueLabel = protocolTechniqueLabel(protocolName);
    const ctx = getSelectedTubeContext();

    if (!ctx.model || ctx.tubeIds.length === 0 || !techniqueLabel) {
      clearWarning();
      return;
    }

    $.ajax({
      url: '/experiments/suitability',
      type: 'POST',
      dataType: 'json',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        protocol: protocolName,
        technique: techniqueLabel,
        model: ctx.model,
        tube_badge_display: window.alephGetTubeBadgeDisplayMode ? window.alephGetTubeBadgeDisplayMode() : 'tube',
        tube_ids: ctx.tubeIds,
      },
      success: function (res) {
        if (res && res.ok) {
          showWarning(res.warnings || []);
        } else {
          clearWarning();
        }
      },
      error: function () {
        // If this fails, we don't block registration; we just hide the warning UI.
        clearWarning();
      },
    });
  }

  $('#protocol').on('change', scheduleSuitabilityCheck);
  $('#model').on('change', function () {
    clearWarning();
    scheduleSuitabilityCheck();
  });

  $(document).on('change', 'input[name="tube_badge_display"], input[name="tube_code_display"]', function () {
    scheduleSuitabilityCheck();
  });

  [
    'human_tube_id',
    'animal_tube_id',
    'environment_tube_id',
    'parasite_tube_id',
    'nucleic_tube_id',
    'culture_tube_id',
    'pool_tube_id',
  ].forEach((id) => {
    const el = document.getElementById(id);
    if (!el || !el.selectize) return;
    el.selectize.on('item_add', scheduleSuitabilityCheck);
    el.selectize.on('item_remove', scheduleSuitabilityCheck);
  });

  $('form[action="/experiments"]').on('submit', function (e) {
    const hasWarning = $warning.attr('data-has-warning') === '1';
    const override = String($override.val() || '0') === '1';
    if (!hasWarning || override) {
      return;
    }

    e.preventDefault();
    const ok = confirm('The selected protocol/technique may not be appropriate for the selected samples. Do you want to proceed anyway?');
    if (ok) {
      $override.val('1');
      $ack.prop('checked', true);
      this.submit();
    }
  });

  // Outcome type radio button functionality
  $('input[name="outcome_type"]').on('change', function() {
    const selectedType = $(this).val();
    toggleOutcomeFields(selectedType);
  });

  // Initialize outcome fields based on default selection
  toggleOutcomeFields('qualitative');

  // Tube selection tables are server-paginated (Laravel pagination links),
  // so we intentionally do NOT initialize DataTables here.
  

  function toggleFields(selectedValue) {
    // Hide all models initially
    document.getElementById("human_model").style.display = "none";
    document.getElementById("animal_model").style.display = "none";
    document.getElementById("environment_model").style.display = "none";
    document.getElementById("parasite_model").style.display = "none";
    document.getElementById("nucleic_model").style.display = "none";
    document.getElementById("culture_model").style.display = "none";
    document.getElementById("pool_model").style.display = "none";

    // Show the model based on the selected value
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

  function toggleOutcomeFields(selectedType) {
    const qualitativeSection = $('#qualitative_outcome_section');
    const quantitativeSection = $('#quantitative_outcome_section');
    const qualitativeSelect = $('#outcome_qual')[0].selectize;
    const quantitativeInput = $('#outcome_quant');

    // Reset validation states
    qualitativeSelect.clear();
    quantitativeInput.val('0.0000');

    switch(selectedType) {
      case 'qualitative':
        qualitativeSection.show();
        quantitativeSection.hide();
        qualitativeSelect.settings.required = true;
        quantitativeInput.prop('required', false);
        break;
      case 'both':
        qualitativeSection.show();
        quantitativeSection.show();
        qualitativeSelect.settings.required = true;
        quantitativeInput.prop('required', true);
        break;
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
    fetchUrl: '/experiments/create/tubes/human'
  });

  setupTubeSelector({
    selectId: 'animal_tube_id',
    modalId: 'animal_tubes_modal',
    openBtnId: 'animal_tubes_btn',
    closeBtnId: 'animal_tubes_close_btn',
    confirmBtnId: 'confirm_tube_selection',
    checkboxClass: 'select-tube',
    masterCheckboxId: 'select_all_tubes',
    fetchUrl: '/experiments/create/tubes/animal'
  });

  setupTubeSelector({
    selectId: 'environment_tube_id',
    modalId: 'environment_tubes_modal',
    openBtnId: 'environment_tubes_btn',
    closeBtnId: 'environment_tubes_close_btn',
    confirmBtnId: 'confirm_environment_tube_selection',
    checkboxClass: 'select-environment-tube',
    masterCheckboxId: 'select_all_environment_tubes',
    fetchUrl: '/experiments/create/tubes/environment'
  });

  setupTubeSelector({
    selectId: 'parasite_tube_id',
    modalId: 'parasite_tubes_modal',
    openBtnId: 'parasite_tubes_btn',
    closeBtnId: 'parasite_tubes_close_btn',
    confirmBtnId: 'confirm_parasite_tube_selection',
    checkboxClass: 'select-parasite-tube',
    masterCheckboxId: 'select_all_parasite_tubes',
    fetchUrl: '/experiments/create/tubes/parasite'
  });

  setupTubeSelector({
    selectId: 'nucleic_tube_id',
    modalId: 'nucleic_tubes_modal',
    openBtnId: 'nucleic_tubes_btn',
    closeBtnId: 'nucleic_tubes_close_btn',
    confirmBtnId: 'confirm_na_tube_selection',
    checkboxClass: 'select-na-tube',
    masterCheckboxId: 'select_all_na_tubes',
    fetchUrl: '/experiments/create/tubes/nucleic'
  });

  setupTubeSelector({
    selectId: 'culture_tube_id',
    modalId: 'culture_tubes_modal',
    openBtnId: 'culture_tubes_btn',
    closeBtnId: 'culture_tubes_close_btn',
    confirmBtnId: 'confirm_culture_tube_selection',
    checkboxClass: 'select-culture-tube',
    masterCheckboxId: 'select_all_culture_tubes',
    fetchUrl: '/experiments/create/tubes/culture'
  });

  setupTubeSelector({
    selectId: 'pool_tube_id',
    modalId: 'pool_tubes_modal',
    openBtnId: 'pool_tubes_btn',
    closeBtnId: 'pool_tubes_close_btn',
    confirmBtnId: 'confirm_pool_tube_selection',
    checkboxClass: 'select-pool-tube',
    masterCheckboxId: 'select_all_pool_tubes',
    fetchUrl: '/experiments/create/tubes/pool'
  });

  
  // Get the Selectize instance correctly
  var selectizeInstance = $modelSelect[0].selectize;
  var initialValue = selectizeInstance.getValue(); // Get the initial value
  toggleFields(initialValue); // Initialize fields
  window.alephRefreshAllTubeBadgeDisplays?.();


document.getElementById('protocol_form_btn').addEventListener('click', function () {
  document.getElementById('protocol_form_modal').classList.remove('hidden');
})

document.getElementById('protocol_form_close_btn').addEventListener('click', function () {
  document.getElementById('protocol_form_modal').classList.add('hidden');
})

document.getElementById('protocol_lookup_btn').addEventListener('click', function () {
  document.getElementById('protocol_lookup_modal').classList.remove('hidden');
  initProtocolLookupTable();
})

document.getElementById('protocol_lookup_close_btn').addEventListener('click', function () {
  document.getElementById('protocol_lookup_modal').classList.add('hidden');
})

document.getElementById('laboratory_lookup_btn').addEventListener('click', function () {
  document.getElementById('laboratory_lookup_modal').classList.remove('hidden');
  initLaboratoryLookupTable();
})

document.getElementById('laboratory_lookup_close_btn').addEventListener('click', function () {
  document.getElementById('laboratory_lookup_modal').classList.add('hidden');
})

document.addEventListener('click', function (event) {
  const protocolButton = event.target.closest('.protocol-table-select');
  if (protocolButton) {
    setExperimentProtocolValue(protocolButton.dataset.protocolName || '');
    document.getElementById('protocol_lookup_modal').classList.add('hidden');
  }

  const laboratoryButton = event.target.closest('.laboratory-table-select');
  if (laboratoryButton) {
    setExperimentLaboratoryValue(laboratoryButton.dataset.laboratoryName || '');
    document.getElementById('laboratory_lookup_modal').classList.add('hidden');
  }
});

document.getElementById('pathogen_protocol_btn').addEventListener('click', function () {
  document.getElementById('pathogen_protocol_modal').classList.remove('hidden');
})

document.getElementById('pathogen_protocol_close_btn').addEventListener('click', function () {
  document.getElementById('pathogen_protocol_modal').classList.add('hidden');
})

document.getElementById('laboratory_btn').addEventListener('click', function () {
  document.getElementById('laboratory_modal').classList.remove('hidden');
})

document.getElementById('laboratory_close_btn').addEventListener('click', function () {
  document.getElementById('laboratory_modal').classList.add('hidden');
})

})

function setupTubeSelector({
  selectId,
  modalId,
  openBtnId,
  closeBtnId,
  confirmBtnId,
  checkboxClass,
  masterCheckboxId,
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

    const allChecked = $(`#${modalId} .${checkboxClass}`).length === $(`#${modalId} .${checkboxClass}:checked`).length;
    $(`#${modalId} #${masterCheckboxId}`).prop('checked', allChecked);

    $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
  }
  
  function updateTubeCount() {
    const count = selectizeControl.items.length;
    $(`#${countSpanId}`).text(`(${count} selected)`);
    window.alephRefreshTubeBadgeDisplay?.(selectId);
  }

  // Update count inside modal (checkboxes checked)
  function updateModalCheckboxCount() {
    const checkedCount = $(`.${checkboxClass}:checked`).length;
    // Update the selected_count span within the modal
    $(`#${modalId} #selected_count`).text(checkedCount);
  }

  // (syncCheckboxesWithSelectize now scoped to the modal)

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

  // Show modal (load content on-demand)
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
  $(document).on('click', `#${modalId} #${confirmBtnId}`, function () {
    selectizeControl.clear(true);

    Array.from(pendingSelectedIds).forEach(function (id) {
      const code = pendingLabelsById[id] || (selectizeControl.options[id] ? (selectizeControl.options[id].code || selectizeControl.options[id].text) : id);
      const alias = pendingAliasesById[id] || (selectizeControl.options[id] ? selectizeControl.options[id].alias_code : '');
      const displayLabel = getTubeDisplayLabel(code, alias);
      if (!selectizeControl.options[id]) {
        selectizeControl.addOption({ value: id, text: displayLabel, code, alias_code: alias });
      } else {
        selectizeControl.options[id].code = code;
        selectizeControl.options[id].alias_code = alias;
        selectizeControl.options[id].text = displayLabel;
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

  // Sync count if user manually removes or adds items from Selectize input
  selectizeControl.on('item_remove', updateTubeCount);
  selectizeControl.on('item_add', updateTubeCount);

  // Master checkbox toggles all checkboxes (scoped to modal)
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
    $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
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
    $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
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


function checkProtocolValue() {
  const selectedProtocol = document.getElementById('protocol').value.trim();
  const additionalProtocolInputs = document.getElementById('additional-protocol');
  const protocolNames = protocolsList.map(protocol => protocol.name);
  const inputRef = document.getElementById('new_protocol_ref');

  if (protocolNames.includes(selectedProtocol)) {
    inputRef.removeAttribute('required');
    additionalProtocolInputs.style.display = 'none';
  } else {
    inputRef.setAttribute('required', 'required');
    additionalProtocolInputs.style.display = 'block';
  }
}


// Get the success and error message elements from the DOM
const successMessageElement = document.getElementById('successMessage');
const errorMessageElement = document.getElementById('errorMessage');

// Show success message if it exists
if (!window.__experimentsFlashHandled && successMessageElement) {
  window.__experimentsFlashHandled = true;
  Swal.fire({
    icon: 'success',
    title: 'Success',
    text: successMessageElement.textContent,
  });
}

// Show error message if it exists
if (!window.__experimentsFlashHandled && errorMessageElement) {
  window.__experimentsFlashHandled = true;
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: errorMessageElement.textContent,
  });
}



$(document).ready(function () {
    const protocolSelect = $('#protocol');
    const pathogenSelect = $('#pathogen');

    function updatePathogensForProtocol(selectedProtocol) {
        const pathogens = protocolPathogenMap[selectedProtocol] || [];

        // Clear existing options
        pathogenSelect.empty();

        // Populate new options
        pathogens.forEach(function (p) {
            const option = $('<option></option>').attr('value', p.id).text(p.species);
            pathogenSelect.append(option);
        });

        // If using Selectize or Select2
        if (pathogenSelect[0].selectize) {
            const selectize = pathogenSelect[0].selectize;
            selectize.clearOptions();
            selectize.addOption(pathogens.map(p => ({ value: p.id, text: p.species })));
            selectize.refreshOptions(false);
        }
    }

    // Initial update (in case a default is selected)
    updatePathogensForProtocol(protocolSelect.val());

    // Update on change
    protocolSelect.on('change', function () {
        updatePathogensForProtocol($(this).val());
    });
});

// Photo preview functionality
function previewImage(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('photo-preview-container');
    const preview = document.getElementById('photo-preview');
    
    if (file) {
        // Validate file size (50MB limit)
        if (file.size > 50 * 1024 * 1024) {
            alert('File size exceeds 50MB limit. Please choose a smaller file.');
            input.value = '';
            return;
        }
        
        const allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff', 'pdf'];
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (!allowedExt.includes(ext)) {
            alert('Unsupported format. Allowed formats: JPG, PNG, WEBP, TIFF, PDF.');
            input.value = '';
            return;
        }

        // Preview only images (PDF can't be previewed as <img>)
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            // Keep preview hidden for non-images (e.g. PDF)
            preview.src = '';
            previewContainer.classList.add('hidden');
        }
    }
}

function removePhoto() {
    const input = document.getElementById('photo');
    const previewContainer = document.getElementById('photo-preview-container');
    const preview = document.getElementById('photo-preview');
    
    input.value = '';
    preview.src = '';
    previewContainer.classList.add('hidden');
}

$(document).ready(function () {
    const sampleAliasBySelector = {};

    // Initialize Selectize for sample type selection
    $('#sample_type').selectize({
        placeholder: "Select sample type",
        create: false,
        dropdownParent: 'body',
        onChange: function(value) {
            toggleSampleSections(value);
        }
    });

    function setupAjaxSelectize(selectId, searchUrl, placeholder, sampleTypeForCount) {
        const $el = $(`#${selectId}`);

        $el.selectize({
            placeholder: placeholder,
            create: false,
            maxOptions: 50,
            valueField: 'value',
            labelField: 'text',
            searchField: ['text'],
            dropdownParent: 'body',
            load: function (query, callback) {
                $.get(searchUrl, { q: query })
                    .done(function (res) {
                        callback(res);
                    })
                    .fail(function () {
                        callback();
                    });
            },
            onChange: function () {
                updateSampleCount(sampleTypeForCount);
            },
        });
    }

    // Initialize Selectize for each sample type (AJAX search)
    setupAjaxSelectize('human_sample_select', '/samples/process/samples/human/search', 'Select human samples', 'human');
    setupAjaxSelectize('animal_sample_select', '/samples/process/samples/animal/search', 'Select animal samples', 'animal');
    setupAjaxSelectize('environment_sample_select', '/samples/process/samples/environment/search', 'Select environmental samples', 'environment');
    setupAjaxSelectize('parasite_sample_select', '/samples/process/samples/parasite/search', 'Select parasite samples', 'parasite');
    setupAjaxSelectize('nucleic_acid_select', '/samples/process/samples/nucleic/search', 'Select nucleic acids', 'nucleic');
    setupAjaxSelectize('culture_select', '/samples/process/samples/culture/search', 'Select cultures', 'culture');
    setupAjaxSelectize('pool_select', '/samples/process/samples/pool/search', 'Select pools', 'pool');

    // Initialize Selectize for tube type
    $('#tube_type').selectize({
        placeholder: "Select or enter new tube type",
        create: true,
        dropdownParent: 'body'
    });

    // Initialize Selectize for purpose
    $('#purpose').selectize({
        placeholder: "Select or enter new purpose",
        create: true,
        dropdownParent: 'body'
    });

    // Initialize Selectize for preservant
    $('#preservant').selectize({
        placeholder: "Select or enter new preservant",
        create: true,
        dropdownParent: 'body'
    });

    // Initialize Selectize for amount unit
    $('#amount_unit').selectize({
        placeholder: "Select or enter new unit",
        create: true,
        dropdownParent: 'body'
    });

    if ($('#sub_project_id').length) {
        $('#sub_project_id').selectize({
            placeholder: 'Select sub-project',
            create: false,
            dropdownParent: 'body',
            plugins: ['remove_button'],
        });
    }

    // Tables are now server-paginated and loaded on-demand (no DataTables).

    // Setup sample selection functionality
    setupSampleSelector(sampleAliasBySelector, updateAliasCodeSection);
    
    // Setup modal functionality
    setupModals();
    
    // Setup success/error messages
    setupMessages();

    // Show/hide alias code assignment for historical samples
    $('input[name="is_historical"]').on('change', function () {
        updateAliasCodeSection();
    });
    $('#auto_alias_from_source').on('change', function () {
        updateAliasCodeSection();
    });

    // Listen to changes in all sample selectors
    const sampleSelectors = ['human_sample_select', 'animal_sample_select', 'environment_sample_select', 'parasite_sample_select', 'nucleic_acid_select', 'culture_select', 'pool_select'];
    
    sampleSelectors.forEach(function(selectorId) {
        const selectizeControl = $(`#${selectorId}`)[0].selectize;
        if (selectizeControl) {
            selectizeControl.on('item_add', updateAliasCodeSection);
            selectizeControl.on('item_remove', updateAliasCodeSection);
            selectizeControl.on('clear', updateAliasCodeSection);
        }
    });

    // Also listen to changes in the sample type selection
    $('#sample_type')[0].selectize.on('change', function() {
        setTimeout(updateAliasCodeSection, 100);
    });

    // Listen to changes in the aliquots input
    $('#aliquots').on('input', function() {
        updateAliasCodeSection();
    });

    function updateAliasCodeSection() {
        const isHistorical = $('input[name="is_historical"]:checked').val() === '1';
        const selectedSamples = getSelectedSampleEntries();
        const aliquots = parseInt($('#aliquots').val() || '1', 10);
        const totalTubes = selectedSamples.length * aliquots;
        const autoFromSource = $('#auto_alias_from_source').is(':checked');
        $('#total_tubes_count').text(totalTubes);

        if (isHistorical && totalTubes > 0) {
            $('#alias_code_section').show();
            // Generate alias code inputs with better labeling
            let html = '';
            let tubeIndex = 0;
            selectedSamples.forEach(function(entry, sampleIndex) {
                for (let aliquotIndex = 1; aliquotIndex <= aliquots; aliquotIndex++) {
                    const tubeNumber = tubeIndex + 1;
                    const prefilledAlias = autoFromSource ? String(entry.alias || '') : '';
                    const escapedAlias = prefilledAlias
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    html += `<div class="flex items-center space-x-2 mb-2 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <span class="text-gray-700 text-sm font-medium">Tube ${tubeNumber} (Sample: ${entry.code}, Aliquot ${aliquotIndex}/${aliquots}):</span>
                        </div>
                        <input type="text" name="alias_code_assignments[]" 
                               value="${escapedAlias}"
                               data-sample-index="${sampleIndex}" 
                               data-aliquot-index="${aliquotIndex - 1}"
                               class="w-48 px-2 py-1 border border-gray-300 rounded" 
                               placeholder="Enter alias code for ${entry.code}-${aliquotIndex}">
                    </div>`;
                    tubeIndex++;
                }
            });
            $('#alias_code_assignments').html(html);
        } else {
            $('#alias_code_section').hide();
            $('#alias_code_assignments').html('');
        }
    }

    function getSelectedSampleEntries() {
        let selectedSamples = [];
        
        // Get selected samples from each selectize control
        const sampleSelectors = ['human_sample_select', 'animal_sample_select', 'environment_sample_select', 'parasite_sample_select', 'nucleic_acid_select', 'culture_select', 'pool_select'];
        
        sampleSelectors.forEach(function(selectorId) {
            const selectizeControl = $(`#${selectorId}`)[0].selectize;
            if (selectizeControl && selectizeControl.items && selectizeControl.options) {
                selectizeControl.items.forEach(function(itemId) {
                    const option = selectizeControl.options[itemId];
                    if (option && option.text) {
                        const aliasMap = sampleAliasBySelector[selectorId] || {};
                        selectedSamples.push({
                            code: option.text,
                            alias: aliasMap[String(itemId)] || '',
                        });
                    }
                });
            }
        });
        
        return selectedSamples;
    }

    // Initialize alias code section
    updateAliasCodeSection();
});

const processSamplesModalState = {
    baseUrl: null,
    currentUrl: null,
    currentFilters: {},
    filterDebounce: null,
    lastFilterFocus: null,
    syncCheckboxesWithSelectize: null,
    perPage: 50,
    sortCol: null,
    sortDir: 'asc',
    pendingSelectedIds: null,
    pendingLabelsById: null,
};

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

    const perPage = options && options.perPage ? Number(options.perPage) : null;
    if (perPage && [10, 50, 100, 200].includes(perPage)) {
        u.searchParams.set('perPage', String(perPage));
    } else {
        u.searchParams.delete('perPage');
    }

    const sortCol = (options && options.sortCol !== null && options.sortCol !== undefined) ? String(options.sortCol) : '';
    const sortDir = (options && options.sortDir === 'desc') ? 'desc' : 'asc';
    if (sortCol.trim() !== '') {
        u.searchParams.set('sort_col', sortCol);
        u.searchParams.set('sort_dir', sortDir);
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

function ensurePageSizeControl(table) {
    if (!table || !table.parentNode) return;
    const existing = table.parentNode.querySelector('[data-rows-per-page-control="1"]');
    if (existing) return;

    const bar = document.createElement('div');
    bar.setAttribute('data-rows-per-page-control', '1');
    bar.className = 'mb-2 flex items-center justify-end gap-2 text-sm text-gray-600';

    const label = document.createElement('span');
    label.textContent = 'Rows per page:';

    const select = document.createElement('select');
    select.className = 'rounded-md border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';

    [10, 50, 100, 200].forEach((n) => {
        const opt = document.createElement('option');
        opt.value = String(n);
        opt.textContent = String(n);
        if (Number(processSamplesModalState.perPage) === n) {
            opt.selected = true;
        }
        select.appendChild(opt);
    });

    select.addEventListener('change', function () {
        processSamplesModalState.perPage = Number(this.value) || 50;
        loadProcessSamplesModalContent(processSamplesModalState.baseUrl, { preservePage: false });
    });

    bar.appendChild(label);
    bar.appendChild(select);
    table.parentNode.insertBefore(bar, table);
}

function syncCheckboxesWithPending($modal, checkboxClass, masterCheckboxId) {
    const pending = processSamplesModalState.pendingSelectedIds;
    if (!pending) return;

    $modal.find(`.${checkboxClass}`).each(function () {
        const checkbox = $(this);
        const id = checkbox.val();
        checkbox.prop('checked', pending.has(String(id)));
    });

    const allChecked =
        $modal.find(`.${checkboxClass}`).length > 0 &&
        $modal.find(`.${checkboxClass}`).length === $modal.find(`.${checkboxClass}:checked`).length;
    $modal.find(`#${masterCheckboxId}`).prop('checked', allChecked);
}

function loadProcessSamplesModalContent(url, options) {
    const $modal = $('#tableModal');
    const $modalContent = $modal.find('[data-modal-content]').first();

    const silent = Boolean(options && options.silent);
    if (!silent) {
        $modalContent.html('<div class="text-sm text-gray-500">Loading…</div>');
    }

    const baseUrl = processSamplesModalState.baseUrl || url;
    const preservePage = Boolean(options && options.preservePage);
    const resolved = resolveModalUrl(url, processSamplesModalState.currentUrl || baseUrl);
    const normalizedUrl = resolved ? resolved.pathname + (resolved.search || '') : url;
    const requestUrl = buildModalUrlWithFilters(normalizedUrl, processSamplesModalState.currentFilters, baseUrl, {
        preservePage,
        perPage: processSamplesModalState.perPage,
        sortCol: processSamplesModalState.sortCol,
        sortDir: processSamplesModalState.sortDir,
    });
    processSamplesModalState.currentUrl = requestUrl;

    $.get(requestUrl)
        .done(function (html) {
            $modalContent.html(html);
            const table = $modalContent.get(0).querySelector('table');
            if (table) {
                ensurePageSizeControl(table);
                ensureSortableHeaders(table);
                ensureColumnFilters(table, processSamplesModalState.currentFilters, function (colIndex, value, inputEl) {
                    processSamplesModalState.lastFilterFocus = {
                        colIndex,
                        caretStart: inputEl && typeof inputEl.selectionStart === 'number' ? inputEl.selectionStart : null,
                        caretEnd: inputEl && typeof inputEl.selectionEnd === 'number' ? inputEl.selectionEnd : null,
                    };
                    const v = (value || '').toString();
                    if (v.trim() === '') {
                        delete processSamplesModalState.currentFilters[colIndex];
                    } else {
                        processSamplesModalState.currentFilters[colIndex] = v;
                    }
                    if (processSamplesModalState.filterDebounce) {
                        clearTimeout(processSamplesModalState.filterDebounce);
                    }
                    processSamplesModalState.filterDebounce = setTimeout(function () {
                        loadProcessSamplesModalContent(baseUrl, { preservePage: false, silent: true });
                    }, 650);
                });

                // Allow users to type comfortably and apply immediately with Enter.
                const filterInputs = table.querySelectorAll('thead tr[data-column-filters="1"] input[data-filter-col]');
                filterInputs.forEach((input) => {
                    input.addEventListener('keydown', (e) => {
                        if (e.key !== 'Enter') {
                            return;
                        }
                        e.preventDefault();
                        if (processSamplesModalState.filterDebounce) {
                            clearTimeout(processSamplesModalState.filterDebounce);
                            processSamplesModalState.filterDebounce = null;
                        }
                        loadProcessSamplesModalContent(baseUrl, { preservePage: false });
                    });
                });

                const focusState = processSamplesModalState.lastFilterFocus;
                if (focusState && Number.isInteger(focusState.colIndex)) {
                    const selector = `input[data-filter-col="${focusState.colIndex}"]`;
                    const focusInput = table.querySelector(selector);
                    if (focusInput) {
                        focusInput.focus();
                        const valueLength = focusInput.value.length;
                        const caretStart = focusState.caretStart;
                        const caretEnd = focusState.caretEnd;
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

            if (typeof processSamplesModalState.syncCheckboxesWithSelectize === 'function') {
                processSamplesModalState.syncCheckboxesWithSelectize();
            }
        })
        .fail(function () {
            $modalContent.html('<div class="text-sm text-red-600">Failed to load data.</div>');
        });
}

function toggleSampleSections(selectedValue) {
    // Hide all sections first
    $('#human_samples_section').hide();
    $('#animal_samples_section').hide();
    $('#environment_samples_section').hide();
    $('#parasite_samples_section').hide();
    $('#nucleic_acids_section').hide();
    $('#cultures_section').hide();
    $('#pools_section').hide();

    // Show the appropriate section based on selection
    if (selectedValue === 'human') {
        $('#human_samples_section').show();
    } else if (selectedValue === 'animal') {
        $('#animal_samples_section').show();
    } else if (selectedValue === 'environment') {
        $('#environment_samples_section').show();
    } else if (selectedValue === 'parasite') {
        $('#parasite_samples_section').show();
    } else if (selectedValue === 'nucleic') {
        $('#nucleic_acids_section').show();
    } else if (selectedValue === 'culture') {
        $('#cultures_section').show();
    } else if (selectedValue === 'pool') {
        $('#pools_section').show();
    }
}



function updateSampleCount(sampleType) {
    let $select;
    let selectizeControl;
    let countSpanId;
    
    // Get the appropriate selectize control and count span
    if (sampleType === 'human') {
        $select = $('#human_sample_select');
        countSpanId = 'human_sample_select_count';
    } else if (sampleType === 'animal') {
        $select = $('#animal_sample_select');
        countSpanId = 'animal_sample_select_count';
    } else if (sampleType === 'environment') {
        $select = $('#environment_sample_select');
        countSpanId = 'environment_sample_select_count';
    } else if (sampleType === 'parasite') {
        $select = $('#parasite_sample_select');
        countSpanId = 'parasite_sample_select_count';
    } else if (sampleType === 'nucleic') {
        $select = $('#nucleic_acid_select');
        countSpanId = 'nucleic_acid_select_count';
    } else if (sampleType === 'culture') {
        $select = $('#culture_select');
        countSpanId = 'culture_select_count';
    } else if (sampleType === 'pool') {
        $select = $('#pool_select');
        countSpanId = 'pool_select_count';
    }
    
    if (!$select.length) return;
    
    selectizeControl = $select[0].selectize;
    
    if (selectizeControl) {
        const count = selectizeControl.items.length;
        $(`#${countSpanId}`).text(`(${count} selected)`);
    }
}

function setupSampleSelector(sampleAliasBySelector = {}, onSelectionChange = null) {
    // Setup for each sample type
    setupSampleSelectorForType('human', 'human_sample_select', 'human_samples_btn', 'confirm_human_sample_selection', 'select-human-sample', 'select_all_human_samples', 1, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('animal', 'animal_sample_select', 'animal_samples_btn', 'confirm_animal_sample_selection', 'select-animal-sample', 'select_all_animal_samples', 3, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('environment', 'environment_sample_select', 'environment_samples_btn', 'confirm_environment_sample_selection', 'select-environment-sample', 'select_all_environment_samples', 1, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('parasite', 'parasite_sample_select', 'parasite_samples_btn', 'confirm_parasite_sample_selection', 'select-parasite-sample', 'select_all_parasite_samples', null, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('nucleic', 'nucleic_acid_select', 'nucleic_acids_btn', 'confirm_nucleic_acids_selection', 'select-nucleic-acid', 'select_all_nucleic_acids', null, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('culture', 'culture_select', 'cultures_btn', 'confirm_culture_selection', 'select-culture', 'select_all_cultures', null, sampleAliasBySelector, onSelectionChange);
    setupSampleSelectorForType('pool', 'pool_select', 'pools_btn', 'confirm_pools_selection', 'select-pool', 'select_all_pools', null, sampleAliasBySelector, onSelectionChange);

    // Close modal button
    $('#closeTableBtn').on('click', function () {
        $('#tableModal').hide();
    });

    // Cancel buttons inside dynamically loaded modal content
    $(document).on('click', '#tableModal [id$="_cancel_btn"]', function () {
        $('#tableModal').hide();
    });

    // AJAX pagination inside the modal (no full page reload)
    $(document).on('click', '#tableModal nav a[href]', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) {
            return;
        }
        loadProcessSamplesModalContent(href, { preservePage: true });
    });

    // Sorting by clicking column headers (first header row only)
    $(document).on('click', '#tableModal table thead tr:first-child th', function (e) {
        // Ignore checkbox header
        if ($(this).find('input[type="checkbox"]').length) {
            return;
        }

        const colIndex = $(this).index();
        if (processSamplesModalState.sortCol === colIndex) {
            processSamplesModalState.sortDir = processSamplesModalState.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            processSamplesModalState.sortCol = colIndex;
            processSamplesModalState.sortDir = 'asc';
        }

        loadProcessSamplesModalContent(processSamplesModalState.baseUrl, { preservePage: false });
    });
}

function setupSampleSelectorForType(
    type,
    selectId,
    btnId,
    confirmBtnId,
    checkboxClass,
    masterCheckboxId,
    aliasColumnIndex = null,
    sampleAliasBySelector = {},
    onSelectionChange = null
) {
    const $select = $(`#${selectId}`);
    const selectizeControl = $select[0].selectize;
    const countSpanId = `${selectId}_count`;
    const $modal = $('#tableModal');
    const $modalContent = $modal.find('[data-modal-content]').first();

    const routeByType = {
        human: '/samples/process/samples/human',
        animal: '/samples/process/samples/animal',
        environment: '/samples/process/samples/environment',
        parasite: '/samples/process/samples/parasite',
        nucleic: '/samples/process/samples/nucleic',
        culture: '/samples/process/samples/culture',
        pool: '/samples/process/samples/pool',
    };
    
    function updateSampleCount() {
        const count = selectizeControl.items.length;
        $(`#${countSpanId}`).text(`(${count} selected)`);
    }

    // Update count inside modal (checkboxes checked)
    function updateModalCheckboxCount() {
        const checkedCount = pendingSelectedIds.size;
        const $modalCountSpan = $(`#tableModal #selected_count`);
        if ($modalCountSpan.length > 0) {
            $modalCountSpan.text(checkedCount);
        }
    }

    let pendingSelectedIds = new Set();
    let pendingLabelsById = {};
    let pendingAliasById = {};

    function getAliasFromRow(checkbox) {
        if (aliasColumnIndex === null || aliasColumnIndex === undefined) {
            return '';
        }
        const raw = checkbox.closest('tr').find(`td:eq(${aliasColumnIndex})`).text().trim();
        if (!raw || raw.toUpperCase() === 'N/A') {
            return '';
        }
        return raw;
    }

    function syncCheckboxesWithSelectize() {
        syncCheckboxesWithPending($modal, checkboxClass, masterCheckboxId);
        updateModalCheckboxCount();
    }

    // Show modal (load content on demand)
    $(`#${btnId}`).on('click', function () {
        $modal.show();
        processSamplesModalState.baseUrl = routeByType[type];
        processSamplesModalState.currentUrl = routeByType[type];
        processSamplesModalState.currentFilters = {};
        processSamplesModalState.perPage = 50;
        processSamplesModalState.sortCol = null;
        processSamplesModalState.sortDir = 'asc';

        pendingSelectedIds = new Set(selectizeControl.items.map(String));
        pendingLabelsById = {};
        pendingAliasById = Object.assign({}, sampleAliasBySelector[selectId] || {});
        selectizeControl.items.forEach(function (itemId) {
            const opt = selectizeControl.options[itemId];
            if (opt && opt.text) {
                pendingLabelsById[String(itemId)] = String(opt.text);
            }
        });

        processSamplesModalState.pendingSelectedIds = pendingSelectedIds;
        processSamplesModalState.pendingLabelsById = pendingLabelsById;
        processSamplesModalState.syncCheckboxesWithSelectize = syncCheckboxesWithSelectize;

        loadProcessSamplesModalContent(routeByType[type], { preservePage: false });
    });

    // Confirm selection (delegated - content is dynamic)
    $(document).on('click', `#tableModal #${confirmBtnId}`, function (e) {
        e.preventDefault();
        e.stopPropagation();
        // Clear previous selections
        selectizeControl.clear(true);

        // Re-add from pending selections (cross-page)
        Array.from(pendingSelectedIds).forEach(function (id) {
            const label = pendingLabelsById[String(id)] || String(id);
            if (!selectizeControl.options[id]) {
                selectizeControl.addOption({ value: id, text: label });
            }
            selectizeControl.addItem(id);
        });

        updateSampleCount();
        $modal.hide();
    });

    // Sync count if user manually removes or adds items from Selectize input
    selectizeControl.on('item_remove', updateSampleCount);
    selectizeControl.on('item_add', updateSampleCount);

    // Master checkbox toggles all checkboxes (delegated - content is dynamic)
    $(document).on('change', `#tableModal #${masterCheckboxId}`, function () {
        const isChecked = $(this).is(':checked');
        $modal.find(`.${checkboxClass}`).each(function () {
            const checkbox = $(this);
            const id = String(checkbox.val());
            checkbox.prop('checked', isChecked);

            if (isChecked) {
                pendingSelectedIds.add(id);
                const label = checkbox.closest('tr').find('td:eq(1)').text().trim();
                if (label) {
                    pendingLabelsById[id] = label;
                }
                pendingAliasById[id] = getAliasFromRow(checkbox);
            } else {
                pendingSelectedIds.delete(id);
                delete pendingAliasById[id];
            }
        });
        updateModalCheckboxCount();
    });

    // Sync master checkbox & update modal count when any checkbox changes
    $(document).on('change', `#tableModal .${checkboxClass}`, function () {
        const id = String($(this).val());
        const isChecked = $(this).is(':checked');

        if (isChecked) {
            pendingSelectedIds.add(id);
            const label = $(this).closest('tr').find('td:eq(1)').text().trim();
            if (label) {
                pendingLabelsById[id] = label;
            }
            pendingAliasById[id] = getAliasFromRow($(this));
        } else {
            pendingSelectedIds.delete(id);
            delete pendingAliasById[id];
        }

        const allChecked =
            $modal.find(`.${checkboxClass}`).length === $modal.find(`.${checkboxClass}:checked`).length;
        $modal.find(`#${masterCheckboxId}`).prop('checked', allChecked);
        updateModalCheckboxCount();
    });

    // Init count
    updateSampleCount();

    const persistAliasMap = function () {
        sampleAliasBySelector[selectId] = Object.assign({}, pendingAliasById);
        if (typeof onSelectionChange === 'function') {
            onSelectionChange();
        }
    };

    $(document).on('change', `#tableModal #${masterCheckboxId}`, persistAliasMap);
    $(document).on('change', `#tableModal .${checkboxClass}`, persistAliasMap);
    $(document).on('click', `#tableModal #${confirmBtnId}`, persistAliasMap);
}

function setupModals() {
    // Close modals when clicking outside
    $(window).on('click', function (event) {
        if ($(event.target).hasClass('modal')) {
            $(event.target).hide();
        }
    });

    // Close modals with Escape key
    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('.modal').hide();
        }
    });
}

function setupMessages() {
    // Get the success and error message elements from the DOM
    const successMessageElement = document.getElementById('successMessage');
    const errorMessageElement = document.getElementById('errorMessage');

    // Show success message if it exists
    if (successMessageElement) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessageElement.textContent,
            confirmButtonColor: '#10B981',
            confirmButtonText: 'OK'
        });
    }

    // Show error message if it exists
    if (errorMessageElement) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessageElement.textContent,
            confirmButtonColor: '#EF4444',
            confirmButtonText: 'OK'
        });
    }
} 
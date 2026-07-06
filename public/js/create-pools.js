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

$(document).ready(function () {
    var $modelSelect = $('#model').selectize({
        placeholder: "Select sample type",
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

    initAjaxSelectize('human_tube_id', 'Enter human tube code', '/samples/pools/create/tubes/human/search');
    initAjaxSelectize('animal_tube_id', 'Enter animal tube code', '/samples/pools/create/tubes/animal/search');
    initAjaxSelectize('environment_tube_id', 'Enter environmental tube code', '/samples/pools/create/tubes/environment/search');
    initAjaxSelectize('parasite_tube_id', 'Enter parasite tube code', '/samples/pools/create/tubes/parasite/search');
    initAjaxSelectize('nucleic_tube_id', 'Enter nucleic tube code', '/samples/pools/create/tubes/nucleic/search');
    initAjaxSelectize('culture_tube_id', 'Enter culture tube code', '/samples/pools/create/tubes/culture/search');

    $('#lab').selectize({
        placeholder: "Search or enter laboratory",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button'],
    });

    $('#pooler').selectize({
        placeholder: "Select person who created the pool",
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

    function toggleFields(selectedValue) {
        document.getElementById("human_model").style.display = "none";
        document.getElementById("animal_model").style.display = "none";
        document.getElementById("environmental_model").style.display = "none";
        document.getElementById("parasite_model").style.display = "none";
        document.getElementById("nucleic_model").style.display = "none";
        document.getElementById("culture_model").style.display = "none";

        if (selectedValue === "Human samples") {
            document.getElementById("human_model").style.display = "block";
        } else if (selectedValue === "Animal samples") {
            document.getElementById("animal_model").style.display = "block";
        } else if (selectedValue === "Environmental samples") {
            document.getElementById("environmental_model").style.display = "block";
        } else if (selectedValue === "Parasite samples") {
            document.getElementById("parasite_model").style.display = "block";
        } else if (selectedValue === "Nucleic acids") {
            document.getElementById("nucleic_model").style.display = "block";
        } else if (selectedValue === "Cultures") {
            document.getElementById("culture_model").style.display = "block";
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
        fetchUrl: '/samples/pools/create/tubes/human'
    });

    setupTubeSelector({
        selectId: 'animal_tube_id',
        modalId: 'animal_tubes_modal',
        openBtnId: 'animal_tubes_btn',
        closeBtnId: 'animal_tubes_close_btn',
        confirmBtnId: 'confirm_tube_selection',
        checkboxClass: 'select-tube',
        masterCheckboxId: 'select_all_tubes',
        fetchUrl: '/samples/pools/create/tubes/animal'
    });

    setupTubeSelector({
        selectId: 'environment_tube_id',
        modalId: 'environment_tubes_modal',
        openBtnId: 'environment_tubes_btn',
        closeBtnId: 'environment_tubes_close_btn',
        confirmBtnId: 'confirm_environment_tube_selection',
        checkboxClass: 'select-environment-tube',
        masterCheckboxId: 'select_all_environment_tubes',
        fetchUrl: '/samples/pools/create/tubes/environment'
    });

    setupTubeSelector({
        selectId: 'parasite_tube_id',
        modalId: 'parasite_tubes_modal',
        openBtnId: 'parasite_tubes_btn',
        closeBtnId: 'parasite_tubes_close_btn',
        confirmBtnId: 'confirm_parasite_tube_selection',
        checkboxClass: 'select-parasite-tube',
        masterCheckboxId: 'select_all_parasite_tubes',
        fetchUrl: '/samples/pools/create/tubes/parasite'
    });

    setupTubeSelector({
        selectId: 'nucleic_tube_id',
        modalId: 'nucleic_tubes_modal',
        openBtnId: 'nucleic_tubes_btn',
        closeBtnId: 'nucleic_tubes_close_btn',
        confirmBtnId: 'confirm_na_tube_selection',
        checkboxClass: 'select-na-tube',
        masterCheckboxId: 'select_all_na_tubes',
        fetchUrl: '/samples/pools/create/tubes/nucleic'
    });

    setupTubeSelector({
        selectId: 'culture_tube_id',
        modalId: 'culture_tubes_modal',
        openBtnId: 'culture_tubes_btn',
        closeBtnId: 'culture_tubes_close_btn',
        confirmBtnId: 'confirm_culture_tube_selection',
        checkboxClass: 'select-culture-tube',
        masterCheckboxId: 'select_all_culture_tubes',
        fetchUrl: '/samples/pools/create/tubes/culture'
    });

    // Get the Selectize instance correctly
    var selectizeInstance = $modelSelect[0].selectize;
    var initialValue = selectizeInstance.getValue();
    toggleFields(initialValue);
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
    fetchUrl,
    aliasColumnIndex = 2
}) {
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
  let lastFilterFocus = null;

    let pendingSelectedIds = new Set();
    let pendingLabelsById = {};
    let pendingAliasesById = {};
    
    function updateTubeCount() {
        const count = selectizeControl.items.length;
        const countSpanId = `${selectId}_count`;
        $(`#${countSpanId}`).text(`(${count} selected)`);
        window.alephRefreshTubeBadgeDisplay?.(selectId);
    }

    function updateModalCheckboxCount() {
        $(`#${modalId} #selected_count`).text(pendingSelectedIds.size);
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
        $(`#${modalId}`).hide();
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
                pendingAliasesById[id] = getAliasFromRow(checkbox);
            } else {
                pendingSelectedIds.delete(id);
                delete pendingAliasesById[id];
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

    // AJAX pagination inside the modal (no full page reload)
    $(document).on('click', `#${modalId} nav a[href]`, function (e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (!url) return;

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
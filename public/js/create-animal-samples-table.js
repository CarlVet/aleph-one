$(document).ready(function () {
  if (window.__animalSamplesTableInitialized) {
    return;
  }
  window.__animalSamplesTableInitialized = true;

  const $tableForm = $('#animal-table-form');
  if (!$tableForm.length) {
    return;
  }

  const $tableBody = $('#animal-registration-table-body');
  const today = new Date().toISOString().slice(0, 10);
  const rowTemplateHtml = ($tableBody.find('tr[data-table-row]').first().prop('outerHTML') || '').trim();
  const existingAnimals = Array.isArray(window.animalTableExistingAnimals) ? window.animalTableExistingAnimals : [];
  const knownSampleTypes = new Set(
    (Array.isArray(window.animalTableKnownSampleTypes) ? window.animalTableKnownSampleTypes : []).map((v) =>
      String(v || '').trim().toLowerCase()
    )
  );
  const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

  function normalizedText(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '');
  }

  function titleCaseWords(value) {
    const lowerWords = new Set([
      'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
      'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
      'a', 'an', 'the',
    ]);

    return String(value || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim()
      .split(' ')
      .filter(Boolean)
      .map((word, index) => {
        if (index > 0 && lowerWords.has(word)) {
          return word;
        }
        return word.charAt(0).toUpperCase() + word.slice(1);
      })
      .join(' ');
  }

  function formatScientificName(value) {
    const normalized = String(value || '')
      .toLowerCase()
      .replace(/\s+/g, ' ')
      .trim();

    if (!normalized) {
      return '';
    }

    const parts = normalized.split(' ');
    parts[0] = parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
    return parts.join(' ');
  }

  function ownerKindFromValue(ownerTypeValue) {
    const v = String(ownerTypeValue || '').toLowerCase();
    if (v.includes('organization')) {
      return 'organization';
    }
    return 'individual';
  }

  function setSelectValue($select, value) {
    const val = value == null ? '' : String(value);
    if ($select[0] && $select[0].selectize) {
      const selectize = $select[0].selectize;
      if (!selectize.options[val]) {
        selectize.addOption({ value: val, text: val });
      }
      selectize.setValue(val, true);
    } else {
      $select.val(val);
    }
  }

  function setSelectDisabled($select, shouldDisable) {
    if ($select[0] && $select[0].selectize) {
      const selectize = $select[0].selectize;
      if (shouldDisable) {
        if (typeof selectize.lock === 'function') {
          selectize.lock();
        } else {
          selectize.disable();
        }
      } else {
        if (typeof selectize.unlock === 'function') {
          selectize.unlock();
        } else {
          selectize.enable();
        }
      }
    } else {
      $select.prop('disabled', shouldDisable);
    }
  }

  function selectizeConfigFor($select) {
    const name = String($select.attr('name') || '');

    if (name.includes('[sample_type]')) {
      return { create: true, placeholder: 'Sample type', dropdownParent: 'body' };
    }
    if (name.includes('[preservant]')) {
      return { create: true, placeholder: 'Preservant', dropdownParent: 'body' };
    }
    if (name.includes('[reason_immobilization]')) {
      return { create: true, placeholder: 'Immobilization reason', dropdownParent: 'body' };
    }
    if (name.includes('[sampling_site]')) {
      return { create: false, placeholder: 'Sampling site', dropdownParent: 'body' };
    }
    if (name.includes('[location]')) {
      return { create: false, placeholder: 'Location', dropdownParent: 'body' };
    }
    if (name.includes('[collector]')) {
      return { create: false, placeholder: 'Collected by', dropdownParent: 'body' };
    }
    if (name.includes('[owner_person]') || name.includes('[owner_organization]')) {
      return { create: false, placeholder: 'Owner', dropdownParent: 'body' };
    }

    return { create: false, dropdownParent: 'body' };
  }

  function ensureSelectizeInModal($modal) {
    if (typeof $.fn.selectize === 'undefined') {
      return;
    }

    const selectConfigs = [
      { selector: 'select[name="sex"]', options: { create: false, dropdownParent: 'body', placeholder: 'Select sex...' } },
      { selector: 'select[name="marital_status"]', options: { create: false, dropdownParent: 'body', placeholder: 'Select marital status...' } },
      { selector: 'select[name="human_country"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter country' } },
      { selector: 'select[name="ethnicity"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter ethnicity' } },
      { selector: 'select[name="occupation"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter occupation' } },
      { selector: 'select[name="preferred_contact_method"]', options: { create: false, dropdownParent: 'body', placeholder: 'Select contact method' } },
      { selector: 'select[name="organization_type"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter organization type' } },
      { selector: 'select[name="organization_country"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter country' } },
      { selector: 'select[name="site_type"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search site type' } },
      { selector: 'select[name="organization_id"]', options: { create: false, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search organization' } },
      { selector: 'select[name="sampling_country"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search country' } },
      { selector: 'select[name="location_type"]', options: { create: true, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search or enter location type' } },
      { selector: 'select[name="lab"]', options: { create: false, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Search laboratory' } },
      { selector: 'select[name="sub_project_id"]', options: { create: false, dropdownParent: 'body', plugins: ['remove_button'], placeholder: 'Select sub-project' } },
    ];

    selectConfigs.forEach((entry) => {
      $modal.find(entry.selector).each(function () {
        if (this.selectize) {
          return;
        }
        $(this).selectize(entry.options);
      });
    });
  }

  function bindCapitalizationRule($modal) {
    const targets = [
      'input[name="first_name"]',
      'input[name="last_name"]',
      'input[name="city"]',
      'input[name="province"]',
      'input[name="street"]',
      'input[name="organization_name"]',
      'input[name="site_name"]',
      'input[name="location_name"]',
      'input[name="region"]',
      'textarea[name="city"]',
      'textarea[name="region"]',
      'textarea[name="address"]',
      'textarea[name="description"]',
      'textarea[name="website"]',
      'input[name="room"]',
    ];

    targets.forEach((selector) => {
      $modal.find(selector).off('blur.tableCase').on('blur.tableCase', function () {
        this.value = titleCaseWords(this.value);
      });
    });
  }

  function bindNameCheck($modal, inputSelector, statusSelector, submitSelector, payloadFactory) {
    const $input = $modal.find(inputSelector).first();
    const $status = $modal.find(statusSelector).first();
    const $submit = $modal.find(submitSelector).first();
    if (!$input.length || !$status.length || !$submit.length) {
      return;
    }

    let timer = null;

    function setSubmitBlocked(blocked) {
      $submit.prop('disabled', blocked);
      $submit.toggleClass('opacity-50 cursor-not-allowed', blocked);
      $submit.toggleClass('hover:scale-105', !blocked);
    }

    function renderStatus(payload) {
      const status = payload && payload.status ? payload.status : 'empty';
      const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

      if (status === 'empty') {
        $status.addClass('hidden').empty();
        setSubmitBlocked(false);
        return;
      }

      if (status === 'exact') {
        $status
          .removeClass('hidden text-yellow-800 text-green-700')
          .addClass('text-red-700')
          .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.');
        setSubmitBlocked(true);
        return;
      }

      if (status === 'similar') {
        const similarTo = suggestions[0] || payload?.match || '';
        $status
          .removeClass('hidden text-red-700 text-green-700')
          .addClass('text-yellow-800')
          .html('<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "' + similarTo + '" option.');
        setSubmitBlocked(false);
        return;
      }

      $status
        .removeClass('hidden text-red-700 text-yellow-800')
        .addClass('text-green-700')
        .html('<i class="fa-solid fa-plus mr-1"></i>Name is available.');
      setSubmitBlocked(false);
    }

    function runCheck() {
      const payload = payloadFactory();
      fetch('/validation/name-check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
      })
        .then((res) => res.json())
        .then((data) => renderStatus(data))
        .catch(() => renderStatus({ status: 'empty' }));
    }

    $input.off('input.tableCheck').on('input.tableCheck', function () {
      clearTimeout(timer);
      timer = setTimeout(runCheck, 350);
    });
    $input.off('blur.tableCheck').on('blur.tableCheck', function () {
      this.value = titleCaseWords(this.value);
      runCheck();
    });
  }

  function bindHumanNameCheck($modal) {
    const $first = $modal.find('input[name="first_name"]').first();
    const $last = $modal.find('input[name="last_name"]').first();
    const $status = $modal.find('#patient_name_status').first();
    const $submit = $modal.find('#human_submit_btn').first();
    if (!$first.length || !$last.length || !$status.length || !$submit.length) {
      return;
    }

    let timer = null;
    function setSubmitBlocked(blocked) {
      $submit.prop('disabled', blocked);
      $submit.toggleClass('opacity-50 cursor-not-allowed', blocked);
      $submit.toggleClass('hover:scale-105', !blocked);
    }
    function render(payload) {
      const status = payload && payload.status ? payload.status : 'empty';
      const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];
      if (status === 'empty') {
        $status.addClass('hidden').empty();
        setSubmitBlocked(false);
        return;
      }
      if (status === 'exact') {
        $status.removeClass('hidden text-yellow-800 text-green-700').addClass('text-red-700')
          .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.');
        setSubmitBlocked(true);
        return;
      }
      if (status === 'similar') {
        const similarTo = suggestions[0] || payload?.match || '';
        $status.removeClass('hidden text-red-700 text-green-700').addClass('text-yellow-800')
          .html('<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "' + similarTo + '" option.');
        setSubmitBlocked(false);
        return;
      }
      $status.removeClass('hidden text-red-700 text-yellow-800').addClass('text-green-700')
        .html('<i class="fa-solid fa-plus mr-1"></i>Name is available.');
      setSubmitBlocked(false);
    }
    function run() {
      const first = titleCaseWords($first.val());
      const last = titleCaseWords($last.val());
      fetch('/validation/name-check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          type: 'patient',
          first_name: first,
          last_name: last,
        }),
      })
        .then((res) => res.json())
        .then((data) => render(data))
        .catch(() => render({ status: 'empty' }));
    }
    [$first, $last].forEach(($el) => {
      $el.off('input.tableHuman').on('input.tableHuman', function () {
        clearTimeout(timer);
        timer = setTimeout(run, 350);
      });
      $el.off('blur.tableHuman').on('blur.tableHuman', function () {
        this.value = titleCaseWords(this.value);
        run();
      });
    });
  }

  function bindAnimalSpeciesNameChecks($modal) {
    const $commonInput = $modal.find('input[name="name_common"]').first();
    const $scientificInput = $modal.find('input[name="name_scientific"]').first();
    const $commonError = $modal.find('#name_common_error').first();
    const $commonSuccess = $modal.find('#name_common_success').first();
    const $scientificError = $modal.find('#name_scientific_error').first();
    const $scientificSuccess = $modal.find('#name_scientific_success').first();
    const $submit = $modal.find('#submitBtn').first();

    if (
      !$commonInput.length ||
      !$scientificInput.length ||
      !$commonError.length ||
      !$commonSuccess.length ||
      !$scientificError.length ||
      !$scientificSuccess.length ||
      !$submit.length
    ) {
      return;
    }

    let commonStatus = 'empty';
    let scientificStatus = 'empty';
    let commonTimer = null;
    let scientificTimer = null;

    function setSubmitBlocked(blocked) {
      $submit.prop('disabled', blocked);
      $submit.toggleClass('opacity-50 cursor-not-allowed', blocked);
      $submit.toggleClass('hover:scale-105', !blocked);
    }

    function updateSubmitState() {
      setSubmitBlocked(commonStatus === 'exact' || scientificStatus === 'exact');
    }

    function renderStatus(status, suggestions, $errorEl, $successEl, setStatus) {
      const similarTo = (Array.isArray(suggestions) && suggestions.length > 0) ? suggestions[0] : '';

      if (status === 'exact') {
        $errorEl
          .removeClass('hidden text-yellow-800')
          .addClass('text-red-700')
          .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.');
        $successEl.addClass('hidden').empty();
      } else if (status === 'similar') {
        $errorEl
          .removeClass('hidden text-red-700')
          .addClass('text-yellow-800')
          .html('<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "' + similarTo + '" option.');
        $successEl.addClass('hidden').empty();
      } else if (status === 'new') {
        $errorEl.addClass('hidden').empty();
        $successEl
          .removeClass('hidden')
          .addClass('text-green-700')
          .html('<i class="fa-solid fa-plus mr-1"></i>Name is available.');
      } else {
        $errorEl.addClass('hidden').empty();
        $successEl.addClass('hidden').empty();
      }

      setStatus(status);
      updateSubmitState();
    }

    function checkDuplicate(field, value, $errorEl, $successEl, setStatus) {
      const normalizedValue = String(value || '').trim();
      if (!normalizedValue) {
        renderStatus('empty', [], $errorEl, $successEl, setStatus);
        return;
      }

      fetch('/animals/species/check-duplicate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          field: field,
          value: normalizedValue,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          const status = data && data.status ? data.status : (data && data.exists ? 'exact' : 'new');
          renderStatus(status, data && data.suggestions ? data.suggestions : [], $errorEl, $successEl, setStatus);
        })
        .catch(() => {
          renderStatus('empty', [], $errorEl, $successEl, setStatus);
        });
    }

    $commonInput.off('input.tableSpecies').on('input.tableSpecies', function () {
      clearTimeout(commonTimer);
      const value = titleCaseWords($commonInput.val());
      commonTimer = setTimeout(function () {
        checkDuplicate('name_common', value, $commonError, $commonSuccess, function (status) {
          commonStatus = status;
        });
      }, 350);
    });
    $commonInput.off('blur.tableSpecies').on('blur.tableSpecies', function () {
      const value = titleCaseWords($commonInput.val());
      $commonInput.val(value);
      checkDuplicate('name_common', value, $commonError, $commonSuccess, function (status) {
        commonStatus = status;
      });
    });

    $scientificInput.off('input.tableSpecies').on('input.tableSpecies', function () {
      clearTimeout(scientificTimer);
      const value = formatScientificName($scientificInput.val());
      scientificTimer = setTimeout(function () {
        checkDuplicate('name_scientific', value, $scientificError, $scientificSuccess, function (status) {
          scientificStatus = status;
        });
      }, 350);
    });
    $scientificInput.off('blur.tableSpecies').on('blur.tableSpecies', function () {
      const value = formatScientificName($scientificInput.val());
      $scientificInput.val(value);
      checkDuplicate('name_scientific', value, $scientificError, $scientificSuccess, function (status) {
        scientificStatus = status;
      });
    });
  }

  function initTableModalForms(modalId) {
    const $modal = $('#' + modalId);
    if (!$modal.length) {
      return;
    }

    ensureSelectizeInModal($modal);
    bindCapitalizationRule($modal);
    bindHumanNameCheck($modal);
    if (typeof window.initOrganizationRegistrationForms === 'function') {
      window.initOrganizationRegistrationForms($modal[0]);
    }
    bindNameCheck(
      $modal,
      'input[name="site_name"]',
      '#site_name_status',
      '#sampling_site_submit_btn',
      function () {
        return { type: 'sampling_site', value: titleCaseWords($modal.find('input[name="site_name"]').first().val()) };
      }
    );
    bindNameCheck(
      $modal,
      'input[name="location_name"]',
      '#location_name_status',
      '#location_submit_btn',
      function () {
        return { type: 'location', value: titleCaseWords($modal.find('input[name="location_name"]').first().val()) };
      }
    );
    bindAnimalSpeciesNameChecks($modal);
  }

  function updateNewSampleTypeCategory($row) {
    const $sampleType = $row.find('select[name*="[sample_type]"]');
    const $category = $row.find('select[name*="[sample_type_category]"]');
    const sampleTypeVal = String($sampleType.val() || '').trim().toLowerCase();
    const isNew = sampleTypeVal !== '' && !knownSampleTypes.has(sampleTypeVal);

    const $categoryCell = $category.closest('td');
    $categoryCell.toggleClass('bg-amber-50', isNew);
    $category.attr('required', isNew);
    setSelectDisabled($category, !isNew);

    if (!isNew) {
      setSelectValue($category, '');
    }
  }

  function updateOwnerVisibility($row) {
    const ownerType = $row.find('.table-owner-type').val() || 'individual';
    const personCell = $row.find('.owner-person-cell');
    const orgCell = $row.find('.owner-org-cell');
    const personSelect = $row.find('.table-owner-person');
    const orgSelect = $row.find('.table-owner-organization');

    if (ownerType === 'organization') {
      personCell.addClass('opacity-40');
      orgCell.removeClass('opacity-40');
      setSelectValue(personSelect, '');
      setSelectDisabled(personSelect, true);
      setSelectDisabled(orgSelect, false);
    } else {
      personCell.removeClass('opacity-40');
      orgCell.addClass('opacity-40');
      setSelectValue(orgSelect, '');
      setSelectDisabled(personSelect, false);
      setSelectDisabled(orgSelect, true);
    }
  }

  function applyExactFieldLabelMode($row, animal) {
    const ownerKind = ownerKindFromValue(animal.owner_type);
    setSelectValue($row.find('.table-animal-species'), animal.animal_species || '');
    setSelectValue($row.find('select[name*="[sex]"]'), animal.sex || '');
    setSelectValue($row.find('select[name*="[age]"]'), animal.age || '');
    setSelectValue($row.find('.table-owner-type'), ownerKind);
    setSelectValue($row.find('.table-owner-person'), ownerKind === 'individual' ? animal.owner_id : '');
    setSelectValue($row.find('.table-owner-organization'), ownerKind === 'organization' ? animal.owner_id : '');

    updateOwnerVisibility($row);

    setSelectDisabled($row.find('.table-animal-species'), true);
    setSelectDisabled($row.find('select[name*="[sex]"]'), true);
    setSelectDisabled($row.find('select[name*="[age]"]'), true);
    setSelectDisabled($row.find('.table-owner-type'), true);
    setSelectDisabled($row.find('.table-owner-person'), true);
    setSelectDisabled($row.find('.table-owner-organization'), true);
    $row.find('.table-open-modal').addClass('pointer-events-none opacity-40');
  }

  function clearExactFieldLabelMode($row) {
    setSelectDisabled($row.find('.table-animal-species'), false);
    setSelectDisabled($row.find('select[name*="[sex]"]'), false);
    setSelectDisabled($row.find('select[name*="[age]"]'), false);
    setSelectDisabled($row.find('.table-owner-type'), false);
    $row.find('.table-open-modal').removeClass('pointer-events-none opacity-40');
    updateOwnerVisibility($row);
  }

  function updateFieldLabelHints($row) {
    const $input = $row.find('.table-field-label');
    const $warning = $row.find('.table-field-label-warning');
    const $warningText = $row.find('.table-field-label-warning-text');
    const value = String($input.val() || '').trim();

    if (!value) {
      $warning.addClass('hidden');
      $warningText.text('');
      clearExactFieldLabelMode($row);
      return;
    }

    const valueNorm = normalizedText(value);
    const exact = existingAnimals.find((animal) => normalizedText(animal.field_label) === valueNorm);

    if (exact) {
      $warning.removeClass('hidden');
      $warningText.text(`Exact field label match found (${exact.field_label}). Animal info is auto-filled and locked.`);
      applyExactFieldLabelMode($row, exact);
      return;
    }

    clearExactFieldLabelMode($row);

    const similar = existingAnimals.find((animal) => {
      const candidateNorm = normalizedText(animal.field_label);
      if (!candidateNorm || !valueNorm) {
        return false;
      }
      return candidateNorm.includes(valueNorm) || valueNorm.includes(candidateNorm);
    });

    if (similar) {
      $warning.removeClass('hidden');
      $warningText.text(`Similar field label exists (${similar.field_label}). Please double-check this is not the same animal.`);
      return;
    }

    $warning.addClass('hidden');
    $warningText.text('');
  }

  function initRowSelectize($row) {
    if (typeof $.fn.selectize === 'undefined') {
      return;
    }

    $row.find('select.table-selectized').each(function () {
      const $select = $(this);
      if (this.selectize) {
        return;
      }
      const cfg = selectizeConfigFor($select);
      $select.selectize(cfg);
    });
  }

  function reindexRows() {
    $tableBody.find('tr[data-table-row]').each(function (index) {
      $(this)
        .find('input[name], select[name], textarea[name]')
        .each(function () {
          const currentName = $(this).attr('name');
          if (!currentName) {
            return;
          }
          const nextName = currentName.replace(/table_rows\[\d+\]/g, `table_rows[${index}]`);
          $(this).attr('name', nextName);
        });
    });
  }

  function setupRowBehavior($row) {
    initRowSelectize($row);
    updateOwnerVisibility($row);
    updateNewSampleTypeCategory($row);
    updateFieldLabelHints($row);
  }

  function initializeExistingRows() {
    $tableBody.find('tr[data-table-row]').each(function () {
      setupRowBehavior($(this));
    });
  }

  $('#animal-table-add-row').off('click').on('click', function () {
    if (!rowTemplateHtml) {
      return;
    }

    const $newRow = $(rowTemplateHtml);
    $newRow.find('input[type="text"], input[type="number"], textarea').val('');
    $newRow.find('input[type="date"]').each(function () {
      const name = String($(this).attr('name') || '');
      if (name.includes('[date]') || name.includes('[date_received]')) {
        $(this).val(today);
      } else {
        $(this).val('');
      }
    });
    $newRow.find('select').each(function () {
      $(this).val('');
    });
    $newRow.find('.table-owner-type').val('individual');
    const lockedCollector = String($newRow.find('input[type="hidden"][name*="[collector]"]').val() || '');
    if (lockedCollector !== '') {
      $newRow.find('select[name*="[collector]"]').val(lockedCollector);
    }
    $newRow.find('.table-field-label-warning').addClass('hidden');
    $newRow.find('.table-field-label-warning-text').text('');
    $tableBody.append($newRow);
    reindexRows();
    setupRowBehavior($newRow);
  });

  $(document).off('click', '.animal-table-remove-row').on('click', '.animal-table-remove-row', function () {
    const rowCount = $tableBody.find('tr[data-table-row]').length;
    if (rowCount <= 1) {
      return;
    }

    $(this).closest('tr[data-table-row]').remove();
    reindexRows();
  });

  $(document).off('change', '.table-owner-type').on('change', '.table-owner-type', function () {
    updateOwnerVisibility($(this).closest('tr[data-table-row]'));
  });

  $(document).off('change', 'select[name*="[sample_type]"]').on('change', 'select[name*="[sample_type]"]', function () {
    updateNewSampleTypeCategory($(this).closest('tr[data-table-row]'));
  });

  $(document).off('input', '.table-field-label').on('input', '.table-field-label', function () {
    updateFieldLabelHints($(this).closest('tr[data-table-row]'));
  });

  $(document).off('click', '.table-open-modal').on('click', '.table-open-modal', function () {
    const modalId = $(this).data('modal-target');
    if (!modalId) {
      return;
    }
    const $modal = $('#' + String(modalId));
    if (!$modal.length) {
      return;
    }
    $modal.removeClass('hidden').addClass('flex');
    initTableModalForms(String(modalId));
  });

  $(document).off('click', '#site_organization_form_btn').on('click', '#site_organization_form_btn', function () {
    $('#site_organization_form_modal').removeClass('hidden').addClass('flex');
    initTableModalForms('site_organization_form_modal');
    if (typeof window.initOrganizationRegistrationForms === 'function') {
      window.initOrganizationRegistrationForms(document.getElementById('site_organization_form_modal'));
    }
  });
  $(document).off('click', '#site_organization_form_close_btn').on('click', '#site_organization_form_close_btn', function () {
    $('#site_organization_form_modal').addClass('hidden').removeClass('flex');
  });

  $(document).off('click', '#location_lab_form_btn').on('click', '#location_lab_form_btn', function () {
    $('#location_lab_form_modal').removeClass('hidden').addClass('flex');
    initTableModalForms('location_lab_form_modal');
  });
  $(document).off('click', '#location_lab_form_close_btn').on('click', '#location_lab_form_close_btn', function () {
    $('#location_lab_form_modal').addClass('hidden').removeClass('flex');
  });

  $(document).off('click', '[id^="table_animal_"][id$="_close_btn"]').on('click', '[id^="table_animal_"][id$="_close_btn"]', function () {
    const $modal = $(this).closest('.fixed.inset-0.z-50');
    $modal.addClass('hidden').removeClass('flex');
  });

  initializeExistingRows();
  initTableModalForms('table_animal_species_form_modal');
  initTableModalForms('table_animal_humans_form_modal');
  initTableModalForms('table_animal_organization_form_modal');
  initTableModalForms('animal_site_form_modal');
  initTableModalForms('animal_location_form_modal');
});

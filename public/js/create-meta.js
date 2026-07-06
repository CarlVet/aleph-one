$(document).ready(function () {
    if (window.AlephLookup) {
      window.AlephLookup.register({
        prefix: 'pathogens_lookup',
        rowsKey: 'pathogenLookupRows',
        selectFieldId: 'pathogens_id',
        valueKey: 'id',
        labelKey: 'species',
        columns: [
          { key: 'species', italic: true }, { key: 'genus' }, { key: 'family' },
          { key: 'order' }, { key: 'class' }, { key: 'phylum' },
        ],
      });

      window.AlephLookup.register({
        prefix: 'animal_species_lookup',
        rowsKey: 'animalSpeciesLookupRows',
        selectFieldId: 'animal_species_id',
        valueKey: 'id',
        labelKey: 'label',
        columns: [
          { key: 'name_common' }, { key: 'name_scientific', italic: true }, { key: 'genus' },
          { key: 'family' }, { key: 'order' }, { key: 'class' }, { key: 'phylum' },
        ],
      });

      window.AlephLookup.register({
        prefix: 'parasite_species_lookup',
        rowsKey: 'parasiteSpeciesLookupRows',
        selectFieldId: 'parasite_species_id',
        valueKey: 'id',
        labelKey: 'name_scientific',
        columns: [
          { key: 'name_scientific', italic: true }, { key: 'name_common' }, { key: 'genus' },
          { key: 'family' }, { key: 'order' }, { key: 'class' }, { key: 'phylum' },
        ],
      });
    }

    // Initialize selectize for all select inputs
    var $modelSelect = $('#model').selectize({
        placeholder: "Select data type",
        create: false,
        dropdownParent: 'body',
        onChange: function (value) {
            showFieldsForModel(value);
        }
      });

      if ($('#studies_id').length) {
        $('#studies_id').selectize({
          placeholder: "Search study",
          create: false,
          maxItems: 1,
          valueField: 'value',
          labelField: 'text',
          searchField: ['text'],
          preload: false,
          load: function (query, callback) {
            const url = window.metaStudiesSearchUrl;
            if (!url) return callback();
            if (!query || query.length < 2) return callback();

            fetch(`${url}?q=${encodeURIComponent(query)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then((r) => r.json())
              .then((data) => callback(data))
              .catch(() => callback());
          }
        });
      }

      if ($('#countries_id').length) {
        $('#countries_id').selectize({
          placeholder: "Search country",
          create: true,
          plugins: ['remove_button']
        });
      }

      if ($('#pathogens_id').length) {
        $('#pathogens_id').selectize({
          placeholder: "Search pathogen",
          create: false,
          plugins: ['remove_button']
        });
      }

      if ($('#techniques_id').length) {
        $('#techniques_id').selectize({
          placeholder: "Search technique",
          create: true,
          plugins: ['remove_button'],
          onChange: function (value) {
            checkTechniqueValue(value);
        },
        });
      }

      if ($('#risk_factors_id').length) {
        $('#risk_factors_id').selectize({
          placeholder: "Select or enter new risk factor",
          create: true,
          maxItems: null
        });
      }

      if ($('#people_id').length) {
        $('#people_id').selectize({
          placeholder: "Search person",
          create: false,
          plugins: ['remove_button']
        });
      }

      if ($('#sub_project_id').length) {
        $('#sub_project_id').selectize({
          placeholder: 'Select sub-project',
          create: false,
          dropdownParent: 'body',
          plugins: ['remove_button']
        });
      }

      if ($('#animal_species_id').length) {
        $('#animal_species_id').selectize({
          placeholder: "Search animal species",
          create: false,
          plugins: ['remove_button']
        });
      }

      if ($('#sample_types_id').length) {
        $('#sample_types_id').selectize({
          placeholder: "Search sample type",
          create: true,
          plugins: ['remove_button'],
          onChange: function (value) {
            checkHostDerivedSampleTypeValue(value, 'additional-animal-sample-type', 'animal_sample_category');
          },
        });
      }

      if ($('#clinical_signs_id').length) {
        $('#clinical_signs_id').selectize({
          placeholder: "Search clinical sign",
          create: true,
          maxItems: null
        });
      }

      if ($('#lesions_id').length) {
        $('#lesions_id').selectize({
          placeholder: "Search lesion",
          create: true,
          maxItems: null
        });
      }

      if ($('#human_sample_types_id').length) {
        $('#human_sample_types_id').selectize({
          placeholder: "Search sample type",
          create: true,
          plugins: ['remove_button'],
          onChange: function (value) {
            checkHostDerivedSampleTypeValue(value, 'additional-human-sample-type', 'human_sample_category');
          },
        });
      }

      if ($('#human_signs_id').length) {
        $('#human_signs_id').selectize({
          placeholder: "Search clinical sign",
          create: true,
          maxItems: null
        });
      }

      if ($('#human_lesions_id').length) {
        $('#human_lesions_id').selectize({
          placeholder: "Search lesion",
          create: true,
          maxItems: null
        });
      }

      if ($('#parasite_species_id').length) {
        $('#parasite_species_id').selectize({
          placeholder: "Search parasite species",
          create: false,
          plugins: ['remove_button']
        });
      }

      if ($('#parasite_sample_types_id').length) {
        $('#parasite_sample_types_id').selectize({
          placeholder: "Search sample type",
          create: true,
          plugins: ['remove_button']
        });
      }

      if ($('#environment_sample_types_id').length) {
        $('#environment_sample_types_id').selectize({
          placeholder: "Search sample type",
          create: true,
          plugins: ['remove_button'],
          onChange: function (value) {
            checkEnvironmentSampleValue(value);
        },
        });
      }

      $('#technique_new').selectize({
        placeholder: "Select or enter new technique type",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button']
      });

      $('#environment_sample_category').selectize({
        placeholder: "Select or enter new category",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button']
      });

      if ($('#animal_sample_category').length) {
        $('#animal_sample_category').selectize({
          placeholder: "Select sample category",
          create: false,
          dropdownParent: 'body'
        });
      }

      if ($('#human_sample_category').length) {
        $('#human_sample_category').selectize({
          placeholder: "Select sample category",
          create: false,
          dropdownParent: 'body'
        });
      }

    setupStudySelector({
        selectId: 'studies_id',
        modalId: 'studies_modal',
        openBtnId: 'studies_select_btn',
        closeBtnId: 'studies_close_btn',
        confirmBtnId: 'confirm_study_selection',
        radioClass: 'select-study'
    });

    const animalSampleTypeValue = $('#sample_types_id').val();
    if (typeof animalSampleTypeValue !== 'undefined') {
        checkHostDerivedSampleTypeValue(animalSampleTypeValue, 'additional-animal-sample-type', 'animal_sample_category');
    }

    const humanSampleTypeValue = $('#human_sample_types_id').val();
    if (typeof humanSampleTypeValue !== 'undefined') {
        checkHostDerivedSampleTypeValue(humanSampleTypeValue, 'additional-human-sample-type', 'human_sample_category');
    }

    // NOTE: Studies selection now uses server-side modal table (no DataTables).

    // Get the model select element
    const modelSelect = document.getElementById('model');

    // Get all the specific fields divs
    const animalFields = document.getElementById('animal_fields');
    const humanFields = document.getElementById('human_fields');
    const parasiteFields = document.getElementById('parasite_fields');
    const environmentFields = document.getElementById('environment_fields');

    // Function to hide all specific fields
    function hideAllFields() {
        if (animalFields) animalFields.style.display = 'none';
        if (humanFields) humanFields.style.display = 'none';
        if (parasiteFields) parasiteFields.style.display = 'none';
        if (environmentFields) environmentFields.style.display = 'none';
    }

    // Function to show fields based on selected model
    function showFieldsForModel(model) {
        hideAllFields();

        switch (model) {
            case 'MetaAnimal':
                if (animalFields) animalFields.style.display = 'block';
                break;
            case 'MetaHuman':
                if (humanFields) humanFields.style.display = 'block';
                break;
            case 'MetaParasite':
                if (parasiteFields) parasiteFields.style.display = 'block';
                break;
            case 'MetaEnvironment':
                if (environmentFields) environmentFields.style.display = 'block';
                break;
        }
    }

    var selectizeInstance = $modelSelect[0].selectize;
  var initialValue = selectizeInstance.getValue();

    // Show initial fields based on default selected model
    if (modelSelect) {
        showFieldsForModel(modelSelect.value);
    }


    // Handle form submission
    $('form').on('submit', function (e) {
        const model = $('#model').val();

        // Disable all specific fields first
        $('#animal_fields input, #animal_fields select').prop('disabled', true);
        $('#human_fields input, #human_fields select').prop('disabled', true);
        $('#parasite_fields input, #parasite_fields select').prop('disabled', true);
        $('#environment_fields input, #environment_fields select').prop('disabled', true);

        // Enable only the fields for the selected model
        switch (model) {
            case 'MetaAnimal':
                $('#animal_fields input, #animal_fields select').prop('disabled', false);
                break;
            case 'MetaHuman':
                $('#human_fields input, #human_fields select').prop('disabled', false);
                break;
            case 'MetaParasite':
                $('#parasite_fields input, #parasite_fields select').prop('disabled', false);
                break;
            case 'MetaEnvironment':
                $('#environment_fields input, #environment_fields select').prop('disabled', false);
                break;
        }
    });

    document.getElementById('study_form_btn').addEventListener('click', function () {
        document.getElementById('study_form_modal').classList.remove('hidden');
    })

    document.getElementById('form_close_btn').addEventListener('click', function () {
        document.getElementById('study_form_modal').classList.add('hidden');
    })

    document.getElementById('pathogen_form_btn').addEventListener('click', function () {
        document.getElementById('pathogen_form_modal').classList.remove('hidden');
    })

    document.getElementById('pathogen_form_close_btn').addEventListener('click', function () {
        document.getElementById('pathogen_form_modal').classList.add('hidden');
    })

    document.getElementById('parasite_species_form_btn').addEventListener('click', function () {
        document.getElementById('parasite_species_form_modal').classList.remove('hidden');
    })

    document.getElementById('parasite_species_form_close_btn').addEventListener('click', function () {
        document.getElementById('parasite_species_form_modal').classList.add('hidden');
    })

    document.getElementById('animal_species_form_btn').addEventListener('click', function () {
        document.getElementById('animal_species_form_modal').classList.remove('hidden');
    })

    document.getElementById('animal_species_form_close_btn').addEventListener('click', function () {
        document.getElementById('animal_species_form_modal').classList.add('hidden');
    })


    // Handle flash messages with SweetAlert2
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const validationErrors = JSON.parse(document.getElementById('validationErrors')?.textContent || '[]');

    if (successMessage && successMessage.textContent) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: successMessage.textContent,
            confirmButtonColor: '#3085d6'
        });
    }

    if (errorMessage && errorMessage.textContent) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage.textContent,
            confirmButtonColor: '#d33'
        });
    }

    // Display validation errors if any
    if (validationErrors && validationErrors.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: validationErrors.join('<br>'),
            confirmButtonColor: '#d33'
        });
    }
});

function setupStudySelector({
    selectId,
    modalId,
    openBtnId,
    closeBtnId,
    confirmBtnId,
    radioClass
}) {
    const $select = $(`#${selectId}`);
    const selectizeControl = $select[0].selectize;
    const $modal = $(`#${modalId}`);
    const $modalBody = $('#studies_modal_body');

    function syncRadioWithSelectize() {
        const selectedItem = selectizeControl.items[0]; // Only one allowed
        $(`.${radioClass}`).each(function () {
            const radio = $(this);
            radio.prop('checked', radio.val() === selectedItem);
        });
    }

    function currentFilters() {
        const filters = {};
        $modal.find('input[name^="filters["]').each(function () {
            const name = $(this).attr('name');
            const match = name.match(/^filters\[(\d+)\]$/);
            if (!match) return;
            const idx = match[1];
            const value = $(this).val();
            if (value !== null && String(value).trim() !== '') {
                filters[idx] = String(value);
            }
        });
        return filters;
    }

    function buildUrl(baseUrl) {
        const url = new URL(baseUrl, window.location.origin);
        const filters = currentFilters();
        Object.keys(filters).forEach((k) => {
            url.searchParams.set(`filters[${k}]`, filters[k]);
        });
        return url.toString();
    }

    function loadStudiesIntoModal(baseUrl, options = {}) {
        const preserveFocus = options.preserveFocus === true;

        const activeEl = preserveFocus ? document.activeElement : null;
        const activeName =
            activeEl && activeEl.tagName === 'INPUT' && typeof activeEl.name === 'string' && activeEl.name.startsWith('filters[')
                ? activeEl.name
                : null;
        const activePos = preserveFocus && activeEl && typeof activeEl.selectionStart === 'number' ? activeEl.selectionStart : null;

        const filtersSnapshot = preserveFocus ? currentFilters() : null;

        const url = buildUrl(baseUrl);
        // Keep current UI visible to avoid "page reload" feel.
        $modalBody.addClass('opacity-60 pointer-events-none');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.text())
            .then((html) => {
                $modalBody.html(html);

                if (filtersSnapshot) {
                    Object.keys(filtersSnapshot).forEach((k) => {
                        const input = $modalBody.find(`input[name="filters[${k}]"]`).get(0);
                        if (input) {
                            input.value = filtersSnapshot[k];
                        }
                    });
                }

                syncRadioWithSelectize();

                if (activeName) {
                    const input = $modalBody.find(`input[name="${activeName}"]`).get(0);
                    if (input) {
                        input.focus();
                        if (activePos !== null) {
                            input.setSelectionRange(activePos, activePos);
                        }
                    }
                }
            })
            .catch(() => {
                $modalBody.html('<div class="text-sm text-red-600">Failed to load studies.</div>');
            });

        $modalBody.removeClass('opacity-60 pointer-events-none');
    }

    // Show modal
    $(`#${openBtnId}`).on('click', function () {
        $modal.show();
        const baseUrl = window.metaStudiesModalUrl;
        if (baseUrl) {
            loadStudiesIntoModal(baseUrl);
        }
    });

    // Close modal
    $(`#${closeBtnId}`).on('click', function () {
        $modal.hide();
    });

    // Cancel button inside the table
    $modal.on('click', '#studies_cancel_btn', function () {
        $modal.hide();
    });

    // Confirm selection from radio buttons
    $modal.on('click', `#${confirmBtnId}`, function () {
        const $checked = $modal.find(`.${radioClass}:checked`);
        if ($checked.length === 0) {
            alert('Please select a study');
            return;
        }

        const id = $checked.val();
        const optionText = $checked.data('text') || id;

        // Clear previous selection
        selectizeControl.clear(true);

        // Ensure option exists
        if (!selectizeControl.options[id]) {
            selectizeControl.addOption({ value: id, text: optionText });
        }

        selectizeControl.addItem(id);
        $modal.hide();
    });

    // Pagination inside modal
    $modal.on('click', '[data-modal-content] nav a[href]', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        loadStudiesIntoModal(href);
    });

    // Filter inputs inside modal (debounced)
    let filterTimer = null;
    $modal.on('input', 'input[name^="filters["]', function () {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            const baseUrl = window.metaStudiesModalUrl;
            if (!baseUrl) return;
            loadStudiesIntoModal(baseUrl, { preserveFocus: true });
        }, 250);
    });

    // Update Selectize if user interacts directly with it
    selectizeControl.on('item_add', function () {
        syncRadioWithSelectize();
    });
    selectizeControl.on('item_remove', function () {
        syncRadioWithSelectize();
    });
}

function checkTechniqueValue(selectedTechnique) {
    const additionalTechniqueInputs = document.getElementById('additional-type');
    const techniqueNames = techniquesList.map(technique => technique.name);
    const inputTec = document.getElementById('technique_new');

    if (techniqueNames.includes(selectedTechnique)) {
        inputTec.removeAttribute('required');
        additionalTechniqueInputs.style.display = 'none';
    } else {
        inputTec.setAttribute('required', 'required');
        additionalTechniqueInputs.style.display = 'block';
    }
}

function checkEnvironmentSampleValue(selectedSample) {
    const additionalEnvironmentSampleInputs = document.getElementById('additional-environment-sample');
    const sampleNames = environmentSampleList.map(sample => sample.name);
    const inputSample = document.getElementById('environment_sample_category');

    if (sampleNames.includes(selectedSample)) {
        inputSample.removeAttribute('required');
        additionalEnvironmentSampleInputs.style.display = 'none';
    } else {
        inputSample.setAttribute('required', 'required');
        additionalEnvironmentSampleInputs.style.display = 'block';
    }
}

function checkHostDerivedSampleTypeValue(selectedSample, containerId, inputId) {
    const additionalSampleInputs = document.getElementById(containerId);
    const inputSampleCategory = document.getElementById(inputId);
    const sampleTypeNames = Array.isArray(sampleTypeList)
        ? sampleTypeList
            .map(sample => (sample && sample.name ? String(sample.name) : ''))
            .filter(Boolean)
            .map(name => name.trim().toLowerCase())
        : [];

    const selected = (selectedSample || '').toString().trim().toLowerCase();
    const isExisting = sampleTypeNames.includes(selected);

    if (isExisting || selected === '') {
        inputSampleCategory?.removeAttribute('required');
        if (additionalSampleInputs) {
            additionalSampleInputs.style.display = 'none';
        }
    } else {
        inputSampleCategory?.setAttribute('required', 'required');
        if (additionalSampleInputs) {
            additionalSampleInputs.style.display = 'block';
        }
    }
}

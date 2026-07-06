$(document).ready(function () {
  $('#environment_sample_type').selectize({
    placeholder: "Search environmental sample type",
    create: false,
  });

  $('#location').selectize({
    placeholder: "Search location",
    create: false,
    plugins: ['remove_button']
  });


  $('#sampling_site').selectize({
    placeholder: "Search sampling site",
    create: false,
    plugins: ['remove_button']
  });

  $('#collector').selectize({
    placeholder: "Select sample collector",
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

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function updateEnvironmentFieldLabelAssignments() {
    const environmentTypeElement = document.getElementById('environment_sample_type');
    const assignmentList = $('#environment_field_labels_assignment_list');

    if (!environmentTypeElement || !assignmentList.length) {
      return;
    }

    const selectize = environmentTypeElement.selectize;
    const selectedValues = selectize ? (selectize.getValue() || []) : ($('#environment_sample_type').val() || []);
    const values = Array.isArray(selectedValues) ? selectedValues : [selectedValues];

    const currentMap = {};
    assignmentList.find('input[name^="field_labels_by_type["]').each(function () {
      const name = $(this).attr('name') || '';
      const match = name.match(/^field_labels_by_type\[(.*)\]$/);
      if (match && typeof match[1] === 'string') {
        currentMap[match[1]] = $(this).val() || '';
      }
    });

    assignmentList.empty();

    values
      .filter((value) => typeof value === 'string' && value.trim() !== '')
      .forEach((sampleTypeName) => {
        const safeSampleTypeName = escapeHtml(sampleTypeName);
        const currentValue = escapeHtml(currentMap[sampleTypeName] || '');
        const inputName = `field_labels_by_type[${sampleTypeName}]`;

        const assignmentHtml = `
          <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-3">
            <span class="text-sm font-medium text-gray-700">${safeSampleTypeName}</span>
            <input type="text"
              name="${escapeHtml(inputName)}"
              value="${currentValue}"
              placeholder="Optional (leave empty to keep empty)"
              class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        `;

        assignmentList.append(assignmentHtml);
      });
  }

  $('#environment_sample_type').on('change', function () {
    updateEnvironmentFieldLabelAssignments();
  });

  updateEnvironmentFieldLabelAssignments();

  $('#environment_site_form_btn').on('click', function () {
    $('#environment_site_form_modal').removeClass('hidden');
  });
  $('#environment_site_form_close_btn').on('click', function () {
    $('#environment_site_form_modal').addClass('hidden');
  });

  $('#environment_location_form_btn').on('click', function () {
    $('#environment_location_form_modal').removeClass('hidden');
  });
  $('#environment_location_form_close_btn').on('click', function () {
    $('#environment_location_form_modal').addClass('hidden');
  });

  if (window.AlephLookup) {
    window.AlephLookup.register({
      prefix: 'locations_lookup',
      rowsKey: 'locationLookupRows',
      selectFieldId: 'location',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'type' }, { key: 'room' }, { key: 'barcode' },
        { key: 'laboratory' }, { key: 'country' },
      ],
    });

    window.AlephLookup.register({
      prefix: 'sampling_sites_lookup',
      rowsKey: 'samplingSiteLookupRows',
      selectFieldId: 'sampling_site',
      valueKey: 'name',
      labelKey: 'name',
      columns: [
        { key: 'name' }, { key: 'site_type' }, { key: 'country' }, { key: 'region' },
        { key: 'city' }, { key: 'organization' }, { key: 'latitude' }, { key: 'longitude' },
      ],
    });
  }
})


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
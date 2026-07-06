$(document).ready(function () {
  if (!$('#animal-registration-form').length) return;

  $('#animal_species').selectize({
    placeholder: "Search animal species",
    create: false,
    plugins: ['remove_button'],
  });

  $('#humans_id').selectize({
    placeholder: "Search handler/owner",
    create: false,
    plugins: ['remove_button'],
  });

  $('#owner_person').selectize({
    placeholder: "Search owner",
    create: false,
    plugins: ['remove_button'],
  });

  $('#owner_organization').selectize({
    placeholder: "Search owner",
    create: false,
    plugins: ['remove_button'],
  });


  // Animal species modal setup
  if (document.getElementById('animal_species_form_btn')) {
    document.getElementById('animal_species_form_btn').addEventListener('click', function () {
      console.log('Animal species form button clicked');
      document.getElementById('animal_species_form_modal').classList.remove('hidden');
    });
  }

  if (document.getElementById('animal_species_form_close_btn')) {
    document.getElementById('animal_species_form_close_btn').addEventListener('click', function () {
      document.getElementById('animal_species_form_modal').classList.add('hidden');
    });
  }

  // Humans modal setup
  if (document.getElementById('animal_humans_form_btn')) {
    document.getElementById('animal_humans_form_btn').addEventListener('click', function () {
      console.log('Humans form button clicked');
      document.getElementById('animal_humans_form_modal').classList.remove('hidden');
    });
  }

  if (document.getElementById('animal_humans_form_close_btn')) {
    document.getElementById('animal_humans_form_close_btn').addEventListener('click', function () {
      document.getElementById('animal_humans_form_modal').classList.add('hidden');
    });
  }

  // Organization modal setup
  function openOrganizationModal(modalId, closeButtonId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
      return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    if (typeof window.initOrganizationRegistrationForms === 'function') {
      window.initOrganizationRegistrationForms(modal);
    }
  }

  function closeOrganizationModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
      return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  if (document.getElementById('animal_organization_form_btn')) {
    document.getElementById('animal_organization_form_btn').addEventListener('click', function () {
      openOrganizationModal('animal_organization_form_modal', 'animal_organization_form_close_btn');
    });
  }

  if (document.getElementById('animal_organization_form_close_btn')) {
    document.getElementById('animal_organization_form_close_btn').addEventListener('click', function () {
      closeOrganizationModal('animal_organization_form_modal');
    });
  }

  if (document.getElementById('lab_organization_form_btn')) {
    document.getElementById('lab_organization_form_btn').addEventListener('click', function () {
      openOrganizationModal('animal_organization_modal', 'animal_organization_close_btn');
    });
  }

  if (document.getElementById('animal_organization_close_btn')) {
    document.getElementById('animal_organization_close_btn').addEventListener('click', function () {
      closeOrganizationModal('animal_organization_modal');
    });
  }

  // Outcome type radio button functionality
  $('input[name="owner_type"]').on('change', function () {
    const selectedType = $(this).val();
    toggleOwnerFields(selectedType);
  });

  toggleOwnerFields('individual');

  function toggleOwnerFields(selectedType) {
    const individualSection = $('#owner_type_individual');
    const organizationSection = $('#owner_type_organization');
    const individualSelect = $('#owner_person')[0].selectize;
    const organizationInput = $('#owner_organization')[0].selectize;
  
    // Reset validation states
    individualSelect.clear();
  
    switch (selectedType) {
      case 'individual':
        individualSection.show();
        organizationSection.hide();
        individualSelect.settings.required = true;
        organizationInput.settings.required = false;
        break;
      case 'organization':
        individualSection.hide();
        organizationSection.show();
        individualSelect.settings.required = false;
        organizationInput.settings.required = true;
        break;
    }
  }

  // Photo preview functionality
  $('#photo').on('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        // Remove existing preview if any
        $('#photoPreview').remove();
        
        // Create new preview
        const previewHtml = `
          <div class="mt-4">
            <img id="photoPreview" class="w-32 h-32 object-cover rounded border border-gray-300" src="${e.target.result}" alt="Photo preview">
          </div>
        `;
        $('#photo').closest('.space-y-6').append(previewHtml);
      };
      reader.readAsDataURL(file);
    }
  });

  // Field label assignment functionality
  function updateFieldLabelAssignments() {
    const numberOfAnimals = parseInt($('#number_of_animals').val()) || 1;
    const $assignmentList = $('#field_labels_assignment_list');
    
    // Get project code from the hidden input
    const projectCode = $('#project_code').val();
    if (!projectCode) {
      console.warn('Project code not found, cannot generate animal codes');
      return;
    }
    
    // Get selected species
    const species = $('#animal_species').val() || '';
    // Get the current max serial from the hidden input
    let currentMaxSerial = parseInt($('#current_max_animal_serial').val()) || 0;
    
    // The currentMaxSerial represents the last used serial, so the next available is currentMaxSerial + 1
    let nextAvailableSerial = currentMaxSerial + 1;

    // Clear existing assignments
    $assignmentList.empty();
    console.log('updateFieldLabelAssignments called:', {
      numberOfAnimals: numberOfAnimals,
      projectCode: projectCode,
      currentMaxSerial: currentMaxSerial,
      nextAvailableSerial: nextAvailableSerial,
      species: species
    });

    // Create assignment elements for each animal
    for (let i = 1; i <= numberOfAnimals; i++) {
      const animalCode = projectCode + '-AN-' + (nextAvailableSerial + i - 1);
      console.log(`Generated animal code ${i}: ${animalCode} (nextAvailableSerial: ${nextAvailableSerial}, currentMaxSerial: ${currentMaxSerial})`);
      
      const assignmentHtml = `
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
          <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-700">${animalCode}</span>
            <span class="text-xs text-gray-500">(${species})</span>
          </div>
          <div class="flex items-center space-x-2">
            <input type="text" 
                   name="field_labels[]" 
                   placeholder="e.g., KNP${String(i).padStart(3, '0')}, AM${String(i).padStart(3, '0')}"
                   class="field-label-input w-32 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   required>
          </div>
        </div>
      `;
      $assignmentList.append(assignmentHtml);
    }
    
    console.log('Field label inputs created:', $('#field_labels_assignment_list input[name="field_labels[]"]').length);
  }

  // Remove all previous event bindings before adding new one
  $('#number_of_animals').off('input change keyup');
  $('#number_of_animals').on('input change keyup', function() {
    updateFieldLabelAssignments();
  });
  $('#animal_species').off('change');
  $('#animal_species').on('change', function() {
    updateFieldLabelAssignments();
  });

  // Initial update
  updateFieldLabelAssignments();

  // Test button for field labels
  $('#test_field_labels').on('click', function() {
    console.log('Test button clicked');
    updateFieldLabelAssignments();
    console.log('Field label inputs after update:', $('input[name="field_labels[]"]').length);
    $('input[name="field_labels[]"]').each(function(index) {
      console.log(`Input ${index}:`, $(this).val());
    });
  });



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


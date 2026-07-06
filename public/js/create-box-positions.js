$(document).ready(function () {

  $('#content_type').selectize({
    placeholder: "Select box content type",
    create: false,
    dropdownParent: 'body',
  });

  $('#box').selectize({
    placeholder: "Search and select box",
    create: false,
    dropdownParent: 'body',
  });

  $('#location').selectize({
    placeholder: "Search and select location",
    create: false,
    dropdownParent: 'body',
  });

   $('#mover').selectize({
    placeholder: "Search and select reponsible person",
    create: false,
    dropdownParent: 'body',
  });

   $('#reason').selectize({
    placeholder: "Select or enter reason of movement",
    create: true,
    dropdownParent: 'body',
  });

  if ($('#sub_project_id').length) {
    $('#sub_project_id').selectize({
      placeholder: 'Select sub-project',
      create: false,
      dropdownParent: 'body',
      plugins: ['remove_button'],
    });
  }


  document.getElementById('boxes_btn').addEventListener('click', function () {
  document.getElementById('boxes_modal').classList.remove('hidden');
})

document.getElementById('boxes_close_btn').addEventListener('click', function () {
  document.getElementById('boxes_modal').classList.add('hidden');
})

document.getElementById('box_location_form_btn').addEventListener('click', function () {
  document.getElementById('box_location_form_modal').classList.remove('hidden');
})

document.getElementById('box_location_form_close_btn').addEventListener('click', function () {
  document.getElementById('box_location_form_modal').classList.add('hidden');
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

});

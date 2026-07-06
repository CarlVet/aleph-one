$(document).ready(function() {

    $('#box_content').selectize({
    placeholder: "Select or enter content type",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#box_state').selectize({
    placeholder: "Select or enter content state",
    create: true,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

});



// Success and error message handling
$(document).ready(function() {
    const successMessageElement = document.getElementById('boxesSuccessMessage');
    const errorMessageElement = document.getElementById('boxesErrorMessage');

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

$(document).ready(function() {
    $('#open_document_modal_btn').on('click', function() {
        $('#document_modal').show();
    });

    $('#close_document_modal_btn').on('click', function() {
        $('#document_modal').hide();
    });

    $('#close_edit_document_modal_btn').on('click', function() {
        $('#edit_document_modal').hide();
    });

    function updateEditParentDocumentField() {
        if ($('#edit_document_type_select').val().trim().toLowerCase() === 'amendment') {
            $('#edit_parent_document_field').show();
        } else {
            $('#edit_parent_document_field').hide();
        }
    }

    $('#edit_document_type_select').on('input change', updateEditParentDocumentField);

    $('.edit-document-btn').on('click', function() {
        var $btn = $(this);

        $('#edit_document_form').attr('action', $btn.data('update-url'));
        $('#edit_document_title').val($btn.data('title') || '');
        $('#edit_document_type_select').val($btn.data('type') || '');
        $('#edit_document_description').val($btn.data('description') || '');
        $('#edit_document_date').val($btn.data('document-date') || '');
        $('#edit_document_parent_id').val($btn.data('parent-id') || '');

        updateEditParentDocumentField();
        $('#edit_document_modal').show();
    });

    $('.delete-document-form').on('submit', function(event) {
        if (typeof Swal === 'undefined') {
            return;
        }

        event.preventDefault();
        var form = this;

        Swal.fire({
            icon: 'warning',
            title: 'Delete document?',
            text: 'This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
        }).then(function(result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    var successMessageElement = document.getElementById('documentSuccessMessage');
    var errorMessageElement = document.getElementById('documentErrorMessage');

    if (successMessageElement && typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent });
    }

    if (errorMessageElement && typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent });
    }
});

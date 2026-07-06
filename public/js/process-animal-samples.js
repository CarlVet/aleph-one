$(document).ready(function () {
    
    // Initialize Selectize for sample selection
    $('#sample_select').selectize({
        placeholder: "Select animal samples to process",
        plugins: ['remove_button'],
        create: false,
        maxOptions: 50,
        dropdownParent: 'body',
        items: [],
        onChange: function() {
            updateSampleCount();
        }
    });

    // Initialize Selectize for sample state
    $('#sample_state').selectize({
        placeholder: "Select or enter sample state",
        create: true,
        dropdownParent: 'body'
    });

    // Initialize DataTables
    if ($('#animal_samples_table').length && typeof $.fn.DataTable !== 'undefined') {
        $('#animal_samples_table').DataTable({
            "language": {
                "search": "Filter records:"
            },
            "order": [
                [0, "desc"]
            ],
            "pageLength": 25,
            "responsive": true
        });
    }

    // Setup sample selection functionality
    setupSampleSelector();
    
    // Setup modal functionality
    setupModals();
    
    // Setup success/error messages
    setupMessages();
});

function setupSampleSelector() {
    const $select = $('#sample_select');
    const selectizeControl = $select[0].selectize;
    const $countSpan = $('#sample_select_count');

    function updateSampleCount() {
        const count = selectizeControl.items.length;
        $countSpan.text(`(${count} selected)`);
    }

    function syncCheckboxesWithSelectize() {
        const selectedItems = new Set(selectizeControl.items);

        $('.select-sample').each(function () {
            const checkbox = $(this);
            const id = checkbox.val();
            checkbox.prop('checked', selectedItems.has(id));
        });

        // Update master checkbox state
        const allChecked = $('.select-sample').length === $('.select-sample:checked').length;
        $('#select_all_samples').prop('checked', allChecked);

        updateSampleCount();
    }

    // Show modal on button click
    $('#showTableBtn').on('click', function () {
        syncCheckboxesWithSelectize();
        $('#tableModal').show();
    });

    // Close modal button
    $('#closeTableBtn').on('click', function () {
        $('#tableModal').hide();
    });

    // Handle confirm selection
    $('#confirm_sample_selection').on('click', function () {
        const $checked = $('.select-sample:checked');

        // Clear previous selections
        selectizeControl.clear(true);

        // Re-add only currently checked options
        $checked.each(function () {
            const id = $(this).val();
            const sampleCode = $(this).closest('tr').find('td:eq(1)').text().trim(); // Get the sample code from the first data column

            // Ensure option exists in Selectize
            if (!selectizeControl.options[id]) {
                selectizeControl.addOption({ value: id, text: sampleCode });
            }

            selectizeControl.addItem(id);
        });

        updateSampleCount();
        $('#tableModal').hide();
    });

    // Master checkbox toggles all checkboxes
    $('#select_all_samples').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.select-sample').prop('checked', isChecked);
    });

    // Sync master checkbox when any checkbox changes
    $(document).on('change', '.select-sample', function () {
        const allChecked = $('.select-sample').length === $('.select-sample:checked').length;
        $('#select_all_samples').prop('checked', allChecked);
    });

    // Initialize count
    updateSampleCount();
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

// Form validation
$(document).on('submit', 'form', function(e) {
    const sampleSelect = $('#sample_select')[0].selectize;
    const sampleState = $('#sample_state')[0].selectize;
    const aliquots = $('#aliquots').val();

    let isValid = true;
    let errorMessage = '';

    // Check if samples are selected
    if (sampleSelect.items.length === 0) {
        isValid = false;
        errorMessage += 'Please select at least one animal sample.\n';
    }

    // Check if sample state is selected
    if (!sampleState.items.length) {
        isValid = false;
        errorMessage += 'Please select a sample state.\n';
    }

    // Check if aliquots is valid
    if (!aliquots || aliquots < 1 || aliquots > 20) {
        isValid = false;
        errorMessage += 'Please enter a valid number of aliquots (1-20).\n';
    }

    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: errorMessage,
            confirmButtonColor: '#F59E0B',
            confirmButtonText: 'OK'
        });
    }
});

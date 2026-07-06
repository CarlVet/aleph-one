$(document).ready(function () {

    $('#protocol_ass').selectize({
    placeholder: "Select protocol",
    create: false,
    dropdownParent: 'body',
    plugins: ['remove_button']
  });

  $('#pathogen_ass').selectize({
    placeholder: "Select pathogens",
    dropdownParent: 'body',
  });

  document.getElementById('pathogen_ass_btn').addEventListener('click', function () {
  document.getElementById('pathogen_ass_modal').classList.remove('hidden');
})

document.getElementById('pathogen_ass_close_btn').addEventListener('click', function () {
  document.getElementById('pathogen_ass_modal').classList.add('hidden');
})


});


// Success and error message handling
$(document).ready(function() {
    const successMessageElement = document.getElementById('pathogenProtocolSuccessMessage');
    const errorMessageElement = document.getElementById('pathogenProtocolErrorMessage');

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



$(document).ready(function () {
        const protocolSelect = $('#protocol_ass'); // your select input
        const displayDiv = $('#associated-pathogens-display');

        function updateAssociatedPathogens(protocol) {
            const pathogens = protocolPathogenMap[protocol] || [];

            if (pathogens.length > 0) {
                const listItems = pathogens.map(p => `
                    <li class="flex items-center justify-between group">
                        <span>${p.species}</span>
                        <button class="ml-2 text-red-500 hover:text-red-700 pathogen-detach-btn" data-pathogen-id="${p.id}" data-protocol="${protocol}" title="Detach">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </li>
                `).join('');
                displayDiv.html(`
                    <ul class="list-disc list-inside ml-4 mt-1">
                        ${listItems}
                    </ul>
                `);
            } else {
                displayDiv.html('<span class="text-gray-500 italic">No associated pathogens found for this protocol.</span>');
            }

            // Attach click handler for trash buttons
            $('.pathogen-detach-btn').off('click').on('click', function(e) {
                e.preventDefault();
                const pathogenId = $(this).data('pathogen-id');
                const protocolName = $(this).data('protocol');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will detach the pathogen from the protocol.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, detach it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/pathogens_protocols/detach`,
                            method: 'POST',
                            data: {
                                protocol: protocolName,
                                pathogen_id: pathogenId,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                // Remove the pathogen from the local map and update UI
                                protocolPathogenMap[protocolName] = protocolPathogenMap[protocolName].filter(p => p.id !== pathogenId);
                                updateAssociatedPathogens(protocolName);
                                Swal.fire('Detached!', 'The pathogen has been detached.', 'success');
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to detach pathogen.', 'error');
                            }
                        });
                    }
                });
            });
        }

        // Initial load
        updateAssociatedPathogens(protocolSelect.val());

        // Update on change
        protocolSelect.on('change', function () {
            updateAssociatedPathogens($(this).val());
        });
    });
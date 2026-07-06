// Team modals logic
$(document).ready(function() {

    $('#title').selectize({
        placeholder: "Select or enter title",
        create: true,
        plugins: ['remove_button']
    });

    $('#departments_id').selectize({
        placeholder: "Select department",
        create: false,
        plugins: ['remove_button']
    });

    $('#organizations_id').selectize({
        placeholder: "Select organization",
        create: false,
        plugins: ['remove_button']
    });

    $('#role').selectize({
        placeholder: "Select or enter role",
        create: true,
        plugins: ['remove_button']
    });

    $('#permission').selectize({
        placeholder: "Select permission",
        create: false,
        plugins: ['remove_button']
    });


    // Collaborator modal
    $('#open_team_collaborator_modal_btn').on('click', function() {
        $('#team_collaborator_modal').removeClass('hidden').addClass('flex');
    });
    $('#close_team_collaborator_modal_btn').on('click', function() {
        $('#team_collaborator_modal').addClass('hidden').removeClass('flex');
    });
    $('#team_collaborator_modal').on('click', function(event) {
        if (event.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Organization modal
    $('#team_organization_form_btn').on('click', function() {
        $('#team_organization_form_modal').show();
    });
    $('#team_organization_form_close_btn').on('click', function() {
        $('#team_organization_form_modal').hide();
    });

    // Department modal
    $('#team_department_form_btn').on('click', function() {
        $('#team_department_form_modal').show();
    });
    $('#team_department_form_close_btn').on('click', function() {
        $('#team_department_form_modal').hide();
    });

    // SweetAlert2 for success/error messages
    var successMessageElement = document.getElementById('teamSuccessMessage');
    var errorMessageElement = document.getElementById('teamErrorMessage');
    if (successMessageElement && typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'success', title: 'Success', text: successMessageElement.textContent });
    }
    if (errorMessageElement && typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Error', text: errorMessageElement.textContent });
    } 


    console.log("team.js loaded");

    // Email check for existing person/user
    $(document).on('focus', 'input[name="email"]', function() {
        const emailInput = this;
        const form = emailInput.closest('form');
        if (!form) return;
        if (!emailInput._handlerAttached) {
            emailInput.addEventListener('blur', function() {
                console.log("Email blur triggered", emailInput.value);
                const email = emailInput.value.trim();
                if (!email) return;
                // Try to get CSRF token from meta or hidden input
                let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!csrfToken) {
                    csrfToken = form.querySelector('input[name="_token"]')?.value;
                }
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }
                fetch('/team/check-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('Email check response:', data);
                    if (data.found && data.person) {
                        // Helper to autofill, disable, and add class
                        function autofillAndDisable(selector, value, isDate, isSelectize) {
                            let el = form.querySelector(selector);
                            if (!el) return;
                            if (isDate && value) {
                                // Format date to YYYY-MM-DD
                                let date = new Date(value);
                                if (!isNaN(date)) {
                                    value = date.toISOString().slice(0, 10);
                                } else {
                                    value = value.slice(0, 10); // fallback
                                }
                                el.value = value;
                                el.disabled = true;
                                el.classList.add('autofilled-disabled');
                                return;
                            }
                            if (isSelectize) {
                                let $el = $(el);
                                let selectize = $el[0] && $el[0].selectize;
                                if (selectize) {
                                    selectize.setValue(value || '', true);
                                    selectize.disable();
                                    $el.next('.selectize-control').addClass('autofilled-disabled');
                                }
                                return;
                            }
                            el.value = value || '';
                            el.disabled = true;
                            el.classList.add('autofilled-disabled');
                        }
                        autofillAndDisable('input[name="first_name"]', data.person.first_name);
                        autofillAndDisable('input[name="last_name"]', data.person.last_name);
                        autofillAndDisable('input[name="date_birth"]', data.person.date_birth, true);
                        autofillAndDisable('select[name="departments_id"]', data.person.departments_id, false, true);
                        autofillAndDisable('select[name="organizations_id"]', data.person.organizations_id, false, true);
                        autofillAndDisable('select[name="title"]', data.person.title, false, true);
                        // Add hidden input for person_id
                        let hidden = form.querySelector('input[name="person_id"]');
                        if (!hidden) {
                            hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = 'person_id';
                            form.appendChild(hidden);
                        }
                        hidden.value = data.person.id;
                        // Show notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'Existing Person',
                                text: 'This email is already registered. The form has been auto-filled.'
                            });
                        } else {
                            alert('This email is already registered. The form has been auto-filled.');
                        }
                    } else {
                        // Helper to enable and remove class
                        function enableAndRemoveClass(selector, isSelectize) {
                            let el = form.querySelector(selector);
                            if (!el) return;
                            if (isSelectize) {
                                let $el = $(el);
                                let selectize = $el[0] && $el[0].selectize;
                                if (selectize) {
                                    selectize.enable();
                                    $el.next('.selectize-control').removeClass('autofilled-disabled');
                                }
                                return;
                            }
                            el.disabled = false;
                            el.classList.remove('autofilled-disabled');
                        }
                        enableAndRemoveClass('input[name="first_name"]');
                        enableAndRemoveClass('input[name="last_name"]');
                        enableAndRemoveClass('input[name="date_birth"]');
                        enableAndRemoveClass('select[name="departments_id"]', true);
                        enableAndRemoveClass('select[name="organizations_id"]', true);
                        enableAndRemoveClass('select[name="title"]', true);
                        // Remove hidden input
                        let hidden = form.querySelector('input[name="person_id"]');
                        if (hidden) hidden.remove();
                    }
                })
                .catch(err => {
                    console.error('Email check AJAX error:', err);
                });
            });
            emailInput._handlerAttached = true;
        }
    });
}); 

// Filter departments based on selected organization
function filterDepartments() {
    const selectedOrgId = organizationSelect.value;
    const departmentOptions = departmentSelect.querySelectorAll('option');
    
    departmentOptions.forEach(option => {
        if (option.value === '') {
            option.style.display = 'block'; // Always show the placeholder
        } else {
            const orgId = option.getAttribute('data-organization');
            if (orgId === selectedOrgId) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Reset department selection if it's not valid for the selected organization
    if (departmentSelect.value !== '') {
        const selectedDept = departmentSelect.querySelector(`option[value="${departmentSelect.value}"]`);
        if (selectedDept && selectedDept.style.display === 'none') {
            departmentSelect.value = '';
        }
    }
}


$(document).ready(function () {
    // Initialize selectize for dropdowns
    $('#sex').selectize({
        placeholder: "Select sex...",
        create: false
    });

    $('#marital_status').selectize({
        placeholder: "Select marital status...",
        create: false
    });

    $('#human_country').selectize({
        placeholder: "Search or enter new country",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button']
      });

      $('#ethnicity').selectize({
        placeholder: "Search or enter new ethnicity",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button']
      });

      $('#occupation').selectize({
        placeholder: "Search or enter new occupation",
        create: true,
        dropdownParent: 'body',
        plugins: ['remove_button']
      });

    $('#preferred_contact_method').selectize({
        placeholder: "Select preferred contact method...",
        create: false
    });

    // Show success/error messages using SweetAlert2
    const successMessage = document.getElementById('humanSuccessMessage');
    const errorMessage = document.getElementById('humanErrorMessage');

    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage.textContent,
        });
    }

    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage.textContent,
        });
    }

    function titleCaseWords(value) {
        const lowerWords = new Set([
            'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
            'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
            'a', 'an', 'the'
        ]);

        return (value || '')
            .toString()
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

    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const patientStatus = document.getElementById('patient_name_status');
    const patientSubmit = document.getElementById('human_submit_btn');
    let patientCheckTimer = null;

    function setPatientSubmitBlocked(blocked) {
        if (!patientSubmit) {
            return;
        }
        patientSubmit.disabled = blocked;
        patientSubmit.classList.toggle('opacity-50', blocked);
        patientSubmit.classList.toggle('cursor-not-allowed', blocked);
        patientSubmit.classList.toggle('hover:scale-105', !blocked);
    }

    function renderPatientStatus(payload) {
        if (!patientStatus) {
            return;
        }

        const status = payload && payload.status ? payload.status : 'empty';
        const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

        if (status === 'empty') {
            patientStatus.className = 'mt-1 text-sm hidden';
            patientStatus.innerHTML = '';
            setPatientSubmitBlocked(false);
            return;
        }

        if (status === 'exact') {
            patientStatus.className = 'mt-1 text-sm text-red-700';
            patientStatus.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.';
            setPatientSubmitBlocked(true);
            return;
        }

        if (status === 'similar') {
            const similarTo = suggestions[0] || payload?.match || '';
            patientStatus.className = 'mt-1 text-sm text-yellow-800';
            patientStatus.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`;
            setPatientSubmitBlocked(false);
            return;
        }

        patientStatus.className = 'mt-1 text-sm text-green-700';
        patientStatus.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Name is available.';
        setPatientSubmitBlocked(false);
    }

    function runPatientNameCheck(shouldApplyFormatting = false) {
        const first = firstNameInput ? titleCaseWords(firstNameInput.value) : '';
        const last = lastNameInput ? titleCaseWords(lastNameInput.value) : '';
        if (shouldApplyFormatting) {
            if (firstNameInput) {
                firstNameInput.value = first;
            }
            if (lastNameInput) {
                lastNameInput.value = last;
            }
        }

        fetch('/validation/name-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                type: 'patient',
                first_name: first,
                last_name: last
            })
        })
            .then((response) => response.json())
            .then((data) => renderPatientStatus(data))
            .catch(() => renderPatientStatus({ status: 'empty' }));
    }

    [firstNameInput, lastNameInput].forEach((input) => {
        if (!input) {
            return;
        }

        input.addEventListener('input', () => {
            clearTimeout(patientCheckTimer);
            patientCheckTimer = setTimeout(() => {
                runPatientNameCheck(false);
            }, 350);
        });

        input.addEventListener('blur', () => {
            runPatientNameCheck(true);
        });
    });

    // Photo validation/preview is handled by the reusable Blade component.
});
$(document).ready(function () {
    const $pathogenForms = $('form[action="/pathogens"]');

    function titleCaseSpecies(value) {
        const cleaned = (value || '').toString().toLowerCase().replace(/\s+/g, ' ').trim();
        if (cleaned === '') {
            return '';
        }

        return cleaned
            .split(' ')
            .filter(Boolean)
            .map((word, index) => {
                if (index === 0) {
                    return word.charAt(0).toUpperCase() + word.slice(1);
                }

                return word;
            })
            .join(' ');
    }

    function setSubmitBlocked($submitBtn, blocked) {
        if (!$submitBtn.length) return;
        $submitBtn.prop('disabled', blocked);
        $submitBtn.toggleClass('opacity-50 cursor-not-allowed', blocked);
        $submitBtn.toggleClass('hover:scale-105', !blocked);
    }

    function renderStatus($status, $submitBtn, payload) {
        if (!$status.length) return;
        const status = payload && payload.status ? payload.status : 'empty';
        const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

        if (status === 'empty') {
            $status.addClass('hidden').empty();
            setSubmitBlocked($submitBtn, false);
            return;
        }

        if (status === 'exact') {
            $status
                .removeClass('hidden text-yellow-800 text-green-700')
                .addClass('text-red-700')
                .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Species already exists. Please use the existing pathogen.');
            setSubmitBlocked($submitBtn, true);
            return;
        }

        if (status === 'similar') {
            const similarTo = suggestions[0] || payload?.match || '';
            $status
                .removeClass('hidden text-red-700 text-green-700')
                .addClass('text-yellow-800')
                .html(`<i class="fa-solid fa-triangle-exclamation mr-1"></i>Species is similar to "${similarTo}".`);
            setSubmitBlocked($submitBtn, false);
            return;
        }

        $status
            .removeClass('hidden text-red-700 text-yellow-800')
            .addClass('text-green-700')
            .html('<i class="fa-solid fa-plus mr-1"></i>Species is available.');
        setSubmitBlocked($submitBtn, false);
    }

    function runNameCheck($status, $submitBtn, value) {
        fetch('/validation/name-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                type: 'pathogen',
                value: value
            })
        })
        .then((response) => response.json())
        .then((data) => renderStatus($status, $submitBtn, data))
        .catch(() => renderStatus($status, $submitBtn, { status: 'empty' }));
    }

    $pathogenForms.each(function () {
        const $form = $(this);
        const $speciesInput = $form.find('.pathogen-species-input');
        const $status = $form.find('.pathogen-species-status');
        const $submitBtn = $form.find('.pathogen-submit-btn');
        let nameCheckTimer = null;

        if (!$speciesInput.length) {
            return;
        }

        $speciesInput.on('input', function () {
            const formatted = titleCaseSpecies($(this).val());
            clearTimeout(nameCheckTimer);
            nameCheckTimer = setTimeout(() => {
                runNameCheck($status, $submitBtn, formatted);
            }, 350);
        });

        $speciesInput.on('blur', function () {
            const formatted = titleCaseSpecies($(this).val());
            $(this).val(formatted);
            runNameCheck($status, $submitBtn, formatted);
        });
    });
});

// Success and error message handling
$(document).ready(function() {
    const successMessageElement = document.getElementById('pathogenSuccessMessage');
    const errorMessageElement = document.getElementById('pathogenErrorMessage');

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

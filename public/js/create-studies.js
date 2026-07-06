$(document).ready(function () {
    if ($('#study_design').length) {
        $('#study_design').selectize({
          placeholder: "Search or enter new study design",
          create: true,
          plugins: ['remove_button']
        });
      }

    const $refInput = $('#study_ref');
    const $status = $('#study_ref_status');
    const $submitBtn = $('#study_submit_btn');
    let nameCheckTimer = null;

    function setSubmitBlocked(blocked) {
        if (!$submitBtn.length) return;
        $submitBtn.prop('disabled', blocked);
        $submitBtn.toggleClass('opacity-50 cursor-not-allowed', blocked);
        $submitBtn.toggleClass('hover:scale-105', !blocked);
    }

    function renderStatus(payload) {
        if (!$status.length) return;
        const status = payload && payload.status ? payload.status : 'empty';
        const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

        if (status === 'empty') {
            $status.addClass('hidden').empty();
            setSubmitBlocked(false);
            return;
        }

        if (status === 'exact') {
            $status
                .removeClass('hidden text-yellow-800 text-green-700')
                .addClass('text-red-700')
                .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Reference key already exists. Please use the existing study.');
            setSubmitBlocked(true);
            return;
        }

        if (status === 'similar') {
            const similarTo = suggestions[0] || payload?.match || '';
            $status
                .removeClass('hidden text-red-700 text-green-700')
                .addClass('text-yellow-800')
                .html(`<i class="fa-solid fa-triangle-exclamation mr-1"></i>Reference key is similar to "${similarTo}".`);
            setSubmitBlocked(false);
            return;
        }

        $status
            .removeClass('hidden text-red-700 text-yellow-800')
            .addClass('text-green-700')
            .html('<i class="fa-solid fa-plus mr-1"></i>Reference key is available.');
        setSubmitBlocked(false);
    }

    function runNameCheck(value) {
        fetch('/validation/name-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                type: 'study',
                value: value
            })
        })
        .then((response) => response.json())
        .then((data) => renderStatus(data))
        .catch(() => renderStatus({ status: 'empty' }));
    }

    $refInput.on('input', function () {
        const value = ($(this).val() || '').toString().trim();
        clearTimeout(nameCheckTimer);
        nameCheckTimer = setTimeout(() => {
            runNameCheck(value);
        }, 350);
    });

    $refInput.on('blur', function () {
        const value = ($(this).val() || '').toString().trim();
        $(this).val(value);
        runNameCheck(value);
    });

})


// Success and error message handling
$(document).ready(function() {
    const successMessageElement = document.getElementById('studySuccessMessage');
    const errorMessageElement = document.getElementById('studyErrorMessage');

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

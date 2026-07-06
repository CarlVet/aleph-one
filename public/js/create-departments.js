$(document).ready(function () {
  $('#department_type').selectize({
    placeholder: 'Search or enter new department type',
    create: true,
    plugins: ['remove_button'],
  });

  $('#organization_id').selectize({
    placeholder: 'Search organization',
    create: false,
    plugins: ['remove_button'],
  });

  function titleCaseWords(value) {
    const lowerWords = new Set([
      'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
      'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
      'a', 'an', 'the',
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

  const $nameInput = $('#department_name');
  const $status = $('#department_name_status');
  const $submitBtn = $('#department_submit_btn');
  let nameCheckTimer = null;

  function setSubmitBlocked(blocked) {
    if (!$submitBtn.length) {
      return;
    }

    $submitBtn.prop('disabled', blocked);
    $submitBtn.toggleClass('opacity-50 cursor-not-allowed', blocked);
    $submitBtn.toggleClass('hover:scale-105', !blocked);
  }

  function renderStatus(payload) {
    if (!$status.length) {
      return;
    }

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
        .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.');
      setSubmitBlocked(true);
      return;
    }

    if (status === 'similar') {
      const similarTo = suggestions[0] || payload?.match || '';
      $status
        .removeClass('hidden text-red-700 text-green-700')
        .addClass('text-yellow-800')
        .html(`<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`);
      setSubmitBlocked(false);
      return;
    }

    $status
      .removeClass('hidden text-red-700 text-yellow-800')
      .addClass('text-green-700')
      .html('<i class="fa-solid fa-plus mr-1"></i>Name is available.');
    setSubmitBlocked(false);
  }

  function runNameCheck(value) {
    fetch('/validation/name-check', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({
        type: 'department',
        value: value,
      }),
    })
      .then((response) => response.json())
      .then((data) => renderStatus(data))
      .catch(() => renderStatus({ status: 'empty' }));
  }

  $nameInput.on('input', function () {
    const formatted = titleCaseWords($(this).val());
    clearTimeout(nameCheckTimer);
    nameCheckTimer = setTimeout(() => {
      runNameCheck(formatted);
    }, 350);
  });

  $nameInput.on('blur', function () {
    const formatted = titleCaseWords($(this).val());
    $(this).val(formatted);
    runNameCheck(formatted);
  });
});

$(document).ready(function () {
  const successMessageElement = document.getElementById('departmentSuccessMessage');
  const errorMessageElement = document.getElementById('departmentErrorMessage');

  if (successMessageElement) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: successMessageElement.textContent,
    });
  }

  if (errorMessageElement) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMessageElement.textContent,
    });
  }
});

(function ($) {
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

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function setSubmitBlocked($submit, blocked) {
    if (!$submit.length) {
      return;
    }

    $submit.prop('disabled', blocked);
    $submit.toggleClass('opacity-50 cursor-not-allowed', blocked);
    $submit.toggleClass('hover:scale-105', !blocked);
  }

  function renderStatus($form, $status, $submit, payload) {
    const status = payload && payload.status ? payload.status : 'empty';
    const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

    $form.data('organizationNameStatus', status);

    if (status === 'empty') {
      $status.addClass('hidden').empty();
      setSubmitBlocked($submit, false);
      return;
    }

    if (status === 'exact') {
      $status
        .removeClass('hidden text-yellow-800 text-green-700')
        .addClass('text-red-700')
        .html('<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.');
      setSubmitBlocked($submit, true);
      return;
    }

    if (status === 'similar') {
      const similarTo = suggestions[0] || payload?.match || '';
      $status
        .removeClass('hidden text-red-700 text-green-700')
        .addClass('text-yellow-800')
        .html(`<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`);
      setSubmitBlocked($submit, false);
      return;
    }

    $status
      .removeClass('hidden text-red-700 text-yellow-800')
      .addClass('text-green-700')
      .html('<i class="fa-solid fa-plus mr-1"></i>Name is available.');
    setSubmitBlocked($submit, false);
  }

  function initSelectizeFields($form) {
    $form.find('select[name="organization_type"]').each(function () {
      if (this.selectize) {
        return;
      }

      $(this).selectize({
        placeholder: 'Search or enter new organization type',
        create: true,
        plugins: ['remove_button'],
        dropdownParent: 'body',
      });
    });

    $form.find('select[name="organization_country"]').each(function () {
      if (this.selectize) {
        return;
      }

      $(this).selectize({
        placeholder: 'Search or enter new country',
        create: true,
        plugins: ['remove_button'],
        dropdownParent: 'body',
      });
    });
  }

  function bindOrganizationNameValidation($form) {
    if ($form.data('orgNameValidationInit')) {
      return;
    }

    const $nameInput = $form.find('input[name="organization_name"]').first();
    const $status = $form.find('.organization-name-status').first();
    const $submit = $form.find('.organization-submit-btn').first();

    if (!$nameInput.length || !$status.length || !$submit.length) {
      return;
    }

    $form.data('orgNameValidationInit', true);

    let nameCheckTimer = null;

    function runNameCheck(value) {
      fetch('/validation/name-check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
          type: 'organization',
          value,
        }),
      })
        .then((response) => response.json())
        .then((data) => renderStatus($form, $status, $submit, data))
        .catch(() => renderStatus($form, $status, $submit, { status: 'empty' }));
    }

    $nameInput.off('input.orgNameCheck').on('input.orgNameCheck', function () {
      const formattedForCheck = titleCaseWords($(this).val());
      clearTimeout(nameCheckTimer);
      nameCheckTimer = setTimeout(() => {
        runNameCheck(formattedForCheck);
      }, 350);
    });

    $nameInput.off('blur.orgNameCheck').on('blur.orgNameCheck', function () {
      const formatted = titleCaseWords($(this).val());
      $(this).val(formatted);
      runNameCheck(formatted);
    });

    $form.off('submit.orgNameCheck').on('submit.orgNameCheck', function (event) {
      if ($form.data('organizationNameStatus') === 'exact') {
        event.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Duplicate organization',
            text: 'An organization with this name already exists. Choose it from the dropdown instead.',
          });
        }
      }
    });
  }

  window.initOrganizationRegistrationForms = function (scope) {
    const $root = scope ? $(scope) : $(document);

    $root.find('form.organization-registration-form').each(function () {
      const $form = $(this);
      initSelectizeFields($form);
      bindOrganizationNameValidation($form);
    });
  };

  $(document).ready(function () {
    window.initOrganizationRegistrationForms();
  });

  $(document).ready(function () {
    const successMessageElement = document.getElementById('organizationSuccessMessage');
    const errorMessageElement = document.getElementById('organizationErrorMessage');

    if (successMessageElement && typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: successMessageElement.textContent,
      });
    }

    if (errorMessageElement && typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: errorMessageElement.textContent,
      });
    }
  });
})(jQuery);

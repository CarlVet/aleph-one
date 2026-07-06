$(document).ready(function () {
  if (window.__experimentsTableInitialized) {
    return;
  }
  window.__experimentsTableInitialized = true;

  const $tableForm = $('#experiments-table-form');
  if (!$tableForm.length) {
    return;
  }

  const $tableBody = $('#experiments-registration-table-body');
  const protocolPathogenMap = window.experimentsTableProtocolPathogenMap || {};
  const tubeOptions = Array.isArray(window.experimentsTableTubes) ? window.experimentsTableTubes : [];
  const protocolOptions = Array.isArray(window.experimentsTableProtocols) ? window.experimentsTableProtocols : [];
  const allPathogens = Array.isArray(window.experimentsTableAllPathogens) ? window.experimentsTableAllPathogens : [];
  const laboratoryOptions = Array.isArray(window.experimentsTableLaboratories) ? window.experimentsTableLaboratories : [];
  const peopleOptions = Array.isArray(window.experimentsTablePeople) ? window.experimentsTablePeople : [];
  const lockedRegistrarId = window.experimentsTableLockedRegistrarId == null ? '' : String(window.experimentsTableLockedRegistrarId);
  const canAssignRegistrar = Boolean(window.experimentsTableCanAssignRegistrar);

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function optionHtml(value, label, selectedValue) {
    const selected = String(value) === String(selectedValue || '') ? ' selected' : '';
    return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(label)}</option>`;
  }

  function buildRowHtml(rowIndex) {
    const tubeOptionsHtml = ['<option value=""></option>']
      .concat(tubeOptions.map((tube) => optionHtml(tube.id, tube.label, '')))
      .join('');
    const protocolOptionsHtml = ['<option value=""></option>']
      .concat(protocolOptions.map((protocol) => optionHtml(protocol, protocol, '')))
      .join('');
    const pathogenOptionsHtml = ['<option value=""></option>']
      .concat(allPathogens.map((pathogen) => optionHtml(pathogen, pathogen, '')))
      .join('');
    const outcomeTypeOptionsHtml = ['Qualitative only', 'Both qualitative and quantitative']
      .map((option) => optionHtml(option, option, option === 'Qualitative only' ? 'Qualitative only' : ''))
      .join('');
    const qualitativeOptionsHtml = ['Strong positive', 'Positive', 'Suspect', 'Negative', 'Inconclusive', 'Unsuccessful', 'To be repeated']
      .map((option) => optionHtml(option, option, option === 'Negative' ? 'Negative' : ''))
      .join('');
    const purposeOptionsHtml = ['<option value=""></option>']
      .concat([
        optionHtml('screening', 'Screening', ''),
        optionHtml('confirmation', 'Confirmation', ''),
      ])
      .join('');
    const laboratoryOptionsHtml = ['<option value=""></option>']
      .concat(laboratoryOptions.map((laboratory) => optionHtml(laboratory, laboratory, '')))
      .join('');
    const testerOptionsHtml = ['<option value=""></option>']
      .concat(peopleOptions.map((person) => optionHtml(person.id, person.label, canAssignRegistrar ? '' : lockedRegistrarId)))
      .join('');
    const testerHiddenInput = canAssignRegistrar
      ? ''
      : `<input type="hidden" name="table_rows[${rowIndex}][tested_by]" value="${escapeHtml(lockedRegistrarId)}">`;

    return `
      <tr class="align-top" data-table-row>
        <td class="px-2 py-2 min-w-[260px]">
          <div class="flex items-center gap-2">
            <span class="table-status-icon table-tube-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
            <select name="table_rows[${rowIndex}][tube_id]" class="table-selectized table-tube-select w-full rounded-md border-gray-300" required>
              ${tubeOptionsHtml}
            </select>
          </div>
        </td>
        <td class="px-2 py-2 min-w-[260px]">
          <div class="flex items-center gap-2">
            <span class="table-status-icon table-protocol-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
            <select name="table_rows[${rowIndex}][protocol_name]" class="table-selectized table-protocol-select w-full rounded-md border-gray-300" required>
              ${protocolOptionsHtml}
            </select>
            <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="protocol_form_modal" title="Create new protocol">
              <i class="fas fa-plus text-xs"></i>
            </button>
          </div>
        </td>
        <td class="px-2 py-2 min-w-[260px]">
          <div class="flex items-center gap-2">
            <span class="table-status-icon table-pathogen-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
            <select name="table_rows[${rowIndex}][pathogen]" class="table-selectized table-pathogen-select w-full rounded-md border-gray-300" required>
              ${pathogenOptionsHtml}
            </select>
            <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="pathogen_import_modal" title="Create new pathogen">
              <i class="fas fa-plus text-xs"></i>
            </button>
          </div>
          <button type="button" class="table-open-modal mt-2 inline-flex items-center gap-1 text-[11px] text-amber-700 underline hover:text-amber-600" data-modal-target="pathogen_protocol_modal">
            <i class="fas fa-link"></i>
            Associate pathogen with protocol
          </button>
        </td>
        <td class="px-2 py-2 min-w-[210px]">
          <select name="table_rows[${rowIndex}][outcome_type]" class="table-selectized table-outcome-type-select w-full rounded-md border-gray-300" required>
            ${outcomeTypeOptionsHtml}
          </select>
        </td>
        <td class="px-2 py-2 min-w-[220px]">
          <select name="table_rows[${rowIndex}][outcome_qual]" class="table-selectized w-full rounded-md border-gray-300" required>
            ${qualitativeOptionsHtml}
          </select>
        </td>
        <td class="px-2 py-2 min-w-[180px]">
          <input type="number" step="any" name="table_rows[${rowIndex}][outcome_quant]" class="table-outcome-quant-input w-full rounded-md border-gray-300">
        </td>
        <td class="px-2 py-2 min-w-[180px]">
          <select name="table_rows[${rowIndex}][purpose]" class="table-selectized table-purpose-select w-full rounded-md border-gray-300" required>
            ${purposeOptionsHtml}
          </select>
        </td>
        <td class="px-2 py-2 min-w-[180px]">
          <input type="date" name="table_rows[${rowIndex}][date_tested]" value="${new Date().toISOString().slice(0, 10)}" class="w-full rounded-md border-gray-300" required>
        </td>
        <td class="px-2 py-2 min-w-[240px]">
          <div class="flex items-center gap-2">
            <span class="table-status-icon table-laboratory-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
            <select name="table_rows[${rowIndex}][laboratory]" class="table-selectized table-laboratory-select w-full rounded-md border-gray-300" required>
              ${laboratoryOptionsHtml}
            </select>
            <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="laboratory_modal" title="Create new laboratory">
              <i class="fas fa-plus text-xs"></i>
            </button>
          </div>
        </td>
        <td class="px-2 py-2 min-w-[240px]">
          <div class="flex items-center gap-2">
            <span class="table-status-icon table-tester-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
            <select name="table_rows[${rowIndex}][tested_by]" class="table-selectized w-full rounded-md border-gray-300" required ${canAssignRegistrar ? '' : 'disabled'}>
              ${testerOptionsHtml}
            </select>
            ${testerHiddenInput}
          </div>
        </td>
        <td class="px-2 py-2 min-w-[90px]">
          <button type="button" class="experiments-table-remove-row inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Remove</button>
        </td>
      </tr>
    `;
  }

  function reindexRows() {
    $tableBody.find('tr[data-table-row]').each(function (rowIndex) {
      $(this)
        .find('[name]')
        .each(function () {
          const currentName = $(this).attr('name');
          if (!currentName) {
            return;
          }

          $(this).attr('name', currentName.replace(/table_rows\[\d+\]/g, `table_rows[${rowIndex}]`));
        });
    });
  }

  function selectizeConfigFor($select) {
    const name = String($select.attr('name') || '');

    if (name.includes('[tube_id]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Tube code or alias' };
    }
    if (name.includes('[protocol_name]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Protocol' };
    }
    if (name.includes('[pathogen]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Pathogen' };
    }
    if (name.includes('[laboratory]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Laboratory' };
    }
    if (name.includes('[tested_by]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Tested by' };
    }
    if (name.includes('[purpose]')) {
      return { create: false, dropdownParent: 'body', allowEmptyOption: true, placeholder: 'Test purpose' };
    }

    return { create: false, dropdownParent: 'body', allowEmptyOption: true };
  }

  function initSelectize($context) {
    if (typeof $.fn.selectize === 'undefined') {
      return;
    }

    $context.find('.table-selectized').each(function () {
      if (this.selectize) {
        return;
      }

      $(this).selectize(selectizeConfigFor($(this)));
    });
  }

  function setStatus($row, selector, type) {
    const $status = $row.find(selector).first();
    if (!$status.length) {
      return;
    }

    if (type === 'link') {
      $status.attr('class', `${selector.replace('.', '')} text-blue-700 table-status-icon`);
      $status.html('<i class="fa-solid fa-link"></i>');
      return;
    }

    if (type === 'warning') {
      $status.attr('class', `${selector.replace('.', '')} text-yellow-700 table-status-icon`);
      $status.html('<i class="fa-solid fa-triangle-exclamation"></i>');
      return;
    }

    $status.attr('class', `${selector.replace('.', '')} text-red-700 table-status-icon`);
    $status.html('<i class="fa-solid fa-circle-xmark"></i>');
  }

  function updatePathogenOptions($row) {
    const protocolName = String($row.find('.table-protocol-select').val() || '');
    const currentPathogen = String($row.find('.table-pathogen-select').val() || '');
    const options = protocolName && Array.isArray(protocolPathogenMap[protocolName])
      ? protocolPathogenMap[protocolName]
      : allPathogens.map((species) => ({ species }));
    const $pathogenSelect = $row.find('.table-pathogen-select').first();

    if (!$pathogenSelect.length) {
      return;
    }

    const values = options.map((item) => String(item.species || '').trim()).filter(Boolean);
    if (currentPathogen && !values.includes(currentPathogen)) {
      values.unshift(currentPathogen);
    }

    if ($pathogenSelect[0] && $pathogenSelect[0].selectize) {
      const selectize = $pathogenSelect[0].selectize;
      selectize.clearOptions();
      selectize.addOption({ value: '', text: '' });
      values.forEach((value) => {
        selectize.addOption({ value, text: value });
      });
      selectize.refreshOptions(false);
      selectize.setValue(currentPathogen, true);
      return;
    }

    $pathogenSelect.empty().append('<option value=""></option>');
    values.forEach((value) => {
      const selected = value === currentPathogen ? ' selected' : '';
      $pathogenSelect.append(`<option value="${escapeHtml(value)}"${selected}>${escapeHtml(value)}</option>`);
    });
  }

  function updateQuantitativeState($row) {
    const outcomeType = String($row.find('.table-outcome-type-select').val() || '');
    const $quant = $row.find('.table-outcome-quant-input').first();

    if (!$quant.length) {
      return;
    }

    const required = outcomeType === 'Both qualitative and quantitative';
    $quant.prop('required', required);
    $quant.prop('disabled', !required);

    if (!required) {
      $quant.val('');
    }
  }

  function refreshRowState($row) {
    const tubeId = String($row.find('.table-tube-select').val() || '');
    const protocolName = String($row.find('.table-protocol-select').val() || '');
    const pathogen = String($row.find('.table-pathogen-select').val() || '');
    const laboratory = String($row.find('.table-laboratory-select').val() || '');
    const tester = String($row.find('select[name$="[tested_by]"]').val() || '');
    const associatedPathogens = Array.isArray(protocolPathogenMap[protocolName]) ? protocolPathogenMap[protocolName].map((item) => String(item.species || '')) : [];

    setStatus($row, '.table-tube-status', tubeId ? 'link' : 'missing');
    setStatus($row, '.table-protocol-status', protocolName ? 'link' : 'missing');
    setStatus($row, '.table-laboratory-status', laboratory ? 'link' : 'missing');
    setStatus($row, '.table-tester-status', tester ? 'link' : 'missing');

    if (!pathogen) {
      setStatus($row, '.table-pathogen-status', 'missing');
    } else if (protocolName && associatedPathogens.length && !associatedPathogens.includes(pathogen)) {
      setStatus($row, '.table-pathogen-status', 'warning');
    } else {
      setStatus($row, '.table-pathogen-status', 'link');
    }

    updateQuantitativeState($row);
  }

  function initializeRow($row) {
    initSelectize($row);
    updatePathogenOptions($row);
    refreshRowState($row);
  }

  $('#experiments-table-add-row').off('click').on('click', function () {
    const nextIndex = $tableBody.find('tr[data-table-row]').length;
    const $newRow = $(buildRowHtml(nextIndex));
    $tableBody.append($newRow);
    initializeRow($newRow);
    reindexRows();
  });

  $(document).off('click', '.experiments-table-remove-row').on('click', '.experiments-table-remove-row', function () {
    if ($tableBody.find('tr[data-table-row]').length === 1) {
      return;
    }

    $(this).closest('tr[data-table-row]').remove();
    reindexRows();
  });

  $(document).off('change.experimentsTable', '#experiments-registration-table-body select, #experiments-registration-table-body input')
    .on('change.experimentsTable', '#experiments-registration-table-body select, #experiments-registration-table-body input', function () {
      const $row = $(this).closest('tr[data-table-row]');
      if ($(this).hasClass('table-protocol-select')) {
        updatePathogenOptions($row);
      }
      refreshRowState($row);
    });

  $(document).off('click.experimentsTable', '.table-open-modal').on('click.experimentsTable', '.table-open-modal', function () {
    const modalId = $(this).data('modal-target');
    if (!modalId) {
      return;
    }

    const modal = document.getElementById(String(modalId));
    if (modal) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
  });

  $tableBody.find('tr[data-table-row]').each(function () {
    initializeRow($(this));
  });
});

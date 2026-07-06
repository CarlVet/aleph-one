$(document).ready(function () {
  const tableBody = document.getElementById('microplastics-table-rows');
  const addRowButton = document.getElementById('microplastics-table-add-row');
  const state = window.microplasticsTableState || { tubes: [], protocols: [], mpsTypes: [], laboratories: [], people: [] };

  if (!tableBody || !addRowButton) {
    return;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function selectOptions(items, valueKey = 'value', labelKey = 'label') {
    return items.map((item) => {
      const value = typeof item === 'string' ? item : item[valueKey];
      const label = typeof item === 'string' ? item : item[labelKey];
      return `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
    }).join('');
  }

  function buildRow(index) {
    const today = new Date().toISOString().split('T')[0];

    return `
      <tr data-row-index="${index}">
        <td class="px-4 py-3 min-w-[220px]">
          <select name="table_rows[${index}][tube_id]" class="microplastics-table-select tube-select w-full rounded-lg border border-gray-200 px-3 py-2">
            <option value="">Select tube</option>
            ${selectOptions(state.tubes, 'id', 'label')}
          </select>
        </td>
        <td class="px-4 py-3 min-w-[220px]">
          <select name="table_rows[${index}][protocol_name]" class="microplastics-table-select w-full rounded-lg border border-gray-200 px-3 py-2">
            <option value="">Select protocol</option>
            ${selectOptions(state.protocols)}
          </select>
        </td>
        <td class="px-4 py-3 min-w-[150px]">
          <select name="table_rows[${index}][mps_type]" class="microplastics-table-select w-full rounded-lg border border-gray-200 px-3 py-2">
            <option value="">Select type</option>
            ${selectOptions(state.mpsTypes)}
          </select>
        </td>
        <td class="px-4 py-3 min-w-[120px]">
          <input type="number" step="any" min="0" name="table_rows[${index}][sample_weight]" class="w-full rounded-lg border border-gray-200 px-3 py-2">
        </td>
        <td class="px-4 py-3 min-w-[100px]">
          <input type="number" step="any" min="-1" max="1" name="table_rows[${index}][r_coeff]" class="w-full rounded-lg border border-gray-200 px-3 py-2">
        </td>
        <td class="px-4 py-3 min-w-[120px]">
          <input type="number" step="any" min="0" name="table_rows[${index}][m_feret]" class="w-full rounded-lg border border-gray-200 px-3 py-2">
        </td>
        <td class="px-4 py-3 min-w-[170px]">
          <input type="date" name="table_rows[${index}][identification_date]" value="${today}" class="w-full rounded-lg border border-gray-200 px-3 py-2" required>
        </td>
        <td class="px-4 py-3 min-w-[220px]">
          <select name="table_rows[${index}][laboratory]" class="microplastics-table-select w-full rounded-lg border border-gray-200 px-3 py-2">
            <option value="">Select laboratory</option>
            ${selectOptions(state.laboratories)}
          </select>
        </td>
        <td class="px-4 py-3 min-w-[220px]">
          <select name="table_rows[${index}][identified_by]" class="microplastics-table-select w-full rounded-lg border border-gray-200 px-3 py-2">
            <option value="">Select person</option>
            ${selectOptions(state.people, 'id', 'label')}
          </select>
        </td>
        <td class="px-4 py-3 text-right">
          <button type="button" class="microplastics-table-remove inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }

  function initSelectize(context) {
    $(context).find('.microplastics-table-select').each(function () {
      if (this.selectize) {
        return;
      }

      $(this).selectize({
        create: false,
        dropdownParent: 'body',
        plugins: ['remove_button'],
      });
    });
  }

  function addRow() {
    const index = tableBody.querySelectorAll('tr').length;
    tableBody.insertAdjacentHTML('beforeend', buildRow(index));
    initSelectize(tableBody.lastElementChild);
  }

  addRowButton.addEventListener('click', addRow);

  tableBody.addEventListener('click', function (event) {
    const removeButton = event.target.closest('.microplastics-table-remove');
    if (!removeButton) {
      return;
    }

    const row = removeButton.closest('tr');
    if (row) {
      row.remove();
    }

    if (!tableBody.querySelector('tr')) {
      addRow();
    }
  });

  addRow();
});

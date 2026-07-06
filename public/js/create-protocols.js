$(document).ready(function () {
  
  // Initialize selectize for protocol type with proper data
    $('#protocol_new').selectize({
        valueField: 'name',
        labelField: 'name',
        searchField: ['name'],
        options: typeof techniquesList !== 'undefined' ? techniquesList : [],
        create: true,
        placeholder: "Select or enter new technique",
        onChange: function (value) {
            checkTechniqueValue(value);
        },
        dropdownParent: 'body'
    });

    // The nucleic-acids create modal only contains protocol name + technique type.
    // This script is also used elsewhere, so guard optional widgets/variables.
    if ($('#pathogens_protocol').length && typeof pathogensList !== 'undefined') {
        $('#pathogens_protocol').selectize({
            valueField: 'id',
            labelField: 'species',
            searchField: ['species'],
            options: pathogensList,
            create: true,
            placeholder: "Select or enter pathogen target",
            dropdownParent: 'body'
        });
    }

    if ($('#ref_new').length && typeof studiesList !== 'undefined') {
        $('#ref_new').selectize({
            valueField: 'id',
            labelField: 'ref_key',
            searchField: ['ref_key'],
            options: studiesList,
            placeholder: "Select reference key",
            create: false,
            dropdownParent: 'body'
        });
    }

    if (document.getElementById('study_btn') && document.getElementById('study_modal')) {
        document.getElementById('study_btn').addEventListener('click', function () {
            document.getElementById('study_modal').classList.remove('hidden');
        });
    }

    if (document.getElementById('study_close_btn') && document.getElementById('study_modal')) {
        document.getElementById('study_close_btn').addEventListener('click', function () {
            document.getElementById('study_modal').classList.add('hidden');
        });
    }

    if (document.getElementById('pathogen_btn') && document.getElementById('pathogen_modal')) {
        document.getElementById('pathogen_btn').addEventListener('click', function () {
            document.getElementById('pathogen_modal').classList.remove('hidden');
        });
    }

    if (document.getElementById('pathogen_close_btn') && document.getElementById('pathogen_modal')) {
        document.getElementById('pathogen_close_btn').addEventListener('click', function () {
            document.getElementById('pathogen_modal').classList.add('hidden');
        });
    }

    function titleCaseWords(value) {
        const acronyms = new Set([
            'PCR', 'QPCR', 'RT', 'RTPCR', 'LAMP', 'ELISA', 'DNA', 'RNA', 'RNASEQ', 'ITS', 'COI', 'SNP',
            'IS711', '16S', '18S', 'MLST', 'NGS', 'WGS', 'RFLP', 'LFA', 'IFAT', 'MAT', 'IHA', 'AGID', 'WB',
            'MALDITOF', 'TOF', 'CRISPR', 'LNA', 'TRFLP', 'MIRNA', 'SIRNA', 'MRNA', 'CDNA'
        ]);
        const lowerWords = new Set([
            'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
            'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
            'a', 'an', 'the'
        ]);

        return (value || '')
            .toString()
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ')
            .filter(Boolean)
            .map((word, index) => {
                const cleaned = word.replace(/[^a-zA-Z0-9]/g, '');
                const upper = cleaned.toUpperCase();
                const lower = cleaned.toLowerCase();

                if (index > 0 && lowerWords.has(lower)) {
                    return lower;
                }

                if (acronyms.has(upper)) {
                    if (lower === 'qpcr') {
                        return 'qPCR';
                    }
                    if (lower === 'rtpcr') {
                        return 'rtPCR';
                    }
                    if (lower === 'rnaseq') {
                        return 'RNAseq';
                    }
                    if (lower === 'malditof') {
                        return 'MALDI-TOF';
                    }

                    return upper;
                }

                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            })
            .join(' ');
    }

    const protocolNameInput = document.getElementById('protocol_name');
    const protocolStatus = document.getElementById('protocol_name_status');
    const protocolSubmit = document.getElementById('protocol_submit_btn');
    let protocolCheckTimer = null;

    function setProtocolSubmitBlocked(blocked) {
        if (!protocolSubmit) {
            return;
        }
        protocolSubmit.disabled = blocked;
        protocolSubmit.classList.toggle('opacity-50', blocked);
        protocolSubmit.classList.toggle('cursor-not-allowed', blocked);
        protocolSubmit.classList.toggle('hover:scale-105', !blocked);
    }

    function renderProtocolStatus(payload) {
        if (!protocolStatus) {
            return;
        }

        const status = payload && payload.status ? payload.status : 'empty';
        const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

        if (status === 'empty') {
            protocolStatus.className = 'mt-1 text-sm hidden';
            protocolStatus.innerHTML = '';
            setProtocolSubmitBlocked(false);
            return;
        }

        if (status === 'exact') {
            protocolStatus.className = 'mt-1 text-sm text-red-700';
            protocolStatus.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.';
            setProtocolSubmitBlocked(true);
            return;
        }

        if (status === 'similar') {
            const similarTo = suggestions[0] || payload?.match || '';
            protocolStatus.className = 'mt-1 text-sm text-yellow-800';
            protocolStatus.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`;
            setProtocolSubmitBlocked(false);
            return;
        }

        protocolStatus.className = 'mt-1 text-sm text-green-700';
        protocolStatus.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Name is available.';
        setProtocolSubmitBlocked(false);
    }

    function runProtocolNameCheck(value) {
        fetch('/validation/name-check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                type: 'protocol',
                value: value
            })
        })
            .then((response) => response.json())
            .then((data) => renderProtocolStatus(data))
            .catch(() => renderProtocolStatus({ status: 'empty' }));
    }

    if (protocolNameInput) {
        protocolNameInput.addEventListener('input', function () {
            const formatted = titleCaseWords(protocolNameInput.value);
            clearTimeout(protocolCheckTimer);
            protocolCheckTimer = setTimeout(() => {
                runProtocolNameCheck(formatted);
            }, 350);
        });

        protocolNameInput.addEventListener('blur', function () {
            const formatted = titleCaseWords(protocolNameInput.value);
            protocolNameInput.value = formatted;
            runProtocolNameCheck(formatted);
        });
    }

});


function checkTechniqueValue(selectedTechnique) {
    const additionalTechniqueInputs = document.getElementById('additional-type');
    const techniqueNames = (typeof techniquesList !== 'undefined' ? techniquesList : []).map(technique => technique.name);
    const inputTec = document.getElementById('technique_new');

    if (!additionalTechniqueInputs || !inputTec) {
        return;
    }

    if (techniqueNames.includes(selectedTechnique)) {
        inputTec.removeAttribute('required');
        additionalTechniqueInputs.style.display = 'none';
    } else {
        inputTec.setAttribute('required', 'required');
        additionalTechniqueInputs.style.display = 'block';
    }
}

// Success and error message handling
$(document).ready(function() {
    const successMessageElement = document.getElementById('protocolSuccessMessage');
    const errorMessageElement = document.getElementById('protocolErrorMessage');

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



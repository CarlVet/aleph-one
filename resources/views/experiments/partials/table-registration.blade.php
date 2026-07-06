@php
    $tableRows = old('table_rows', [
        [
            'tube_id' => '',
            'protocol_name' => '',
            'pathogen' => '',
            'outcome_type' => 'Qualitative only',
            'outcome_qual' => 'Negative',
            'outcome_quant' => '',
            'purpose' => '',
            'date_tested' => now()->toDateString(),
            'laboratory' => '',
            'tested_by' => (string) ($locked_registrar_people_id ?? ''),
        ],
    ]);

    $laboratoryOptions = collect($laboratories_by_country ?? [])
        ->flatMap(fn ($labs) => collect($labs)->pluck('name'))
        ->filter()
        ->unique()
        ->values();
@endphp

<div x-show="registerMode === 'table'" x-cloak class="mt-6">
    <x-forms.form method="POST" action="/experiments" id="experiments-table-form">
        @csrf
        <input type="hidden" name="register_mode" value="table">

        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                <div class="text-center mb-2">
                    <h2 class="text-2xl font-bold text-gray-800">Experiments Table Registration</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Register experiments row by row without changing the CSV import workflow.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" id="experiments-table-add-row"
                        class="inline-flex items-center rounded-lg border border-blue-600 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add row
                    </button>
                    <span class="text-sm text-gray-600">Each row creates one experiment.</span>
                </div>

                <div class="text-xs text-gray-600 inline-flex flex-wrap items-center gap-4">
                    <span class="inline-flex items-center gap-1"><i class="fa-solid fa-link text-blue-700"></i> matching values</span>
                    <span class="inline-flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-yellow-700"></i> protocol-pathogen mismatch</span>
                    <span class="inline-flex items-center gap-1"><i class="fa-solid fa-circle-xmark text-red-700"></i> missing required value</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-[1800px] w-full text-sm" id="experiments-registration-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Tube code / alias*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Protocol*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Pathogen*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Outcome type*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Qualitative outcome*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Quantitative outcome</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Test purpose*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Date tested*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Laboratory*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Tested by*</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="experiments-registration-table-body" class="divide-y divide-gray-100">
                            @foreach ($tableRows as $rowIndex => $row)
                                @php
                                    $selectedProtocolName = (string) ($row['protocol_name'] ?? '');
                                    $rowPathogenOptions = collect($protocol_pathogen_map[$selectedProtocolName] ?? $pathogens->map(fn ($pathogen) => ['species' => $pathogen->species])->all())
                                        ->pluck('species')
                                        ->filter()
                                        ->values();
                                @endphp
                                <tr class="align-top" data-table-row>
                                    <td class="px-2 py-2 min-w-[260px]">
                                        <div class="flex items-center gap-2">
                                            <span class="table-status-icon table-tube-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
                                            <select name="table_rows[{{ $rowIndex }}][tube_id]" class="table-selectized table-tube-select w-full rounded-md border-gray-300" required>
                                                <option value=""></option>
                                                @foreach (($table_tube_options ?? []) as $tube)
                                                    <option value="{{ $tube['id'] }}" @selected((string) ($row['tube_id'] ?? '') === (string) $tube['id'])>
                                                        {{ $tube['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 min-w-[260px]">
                                        <div class="flex items-center gap-2">
                                            <span class="table-status-icon table-protocol-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
                                            <select name="table_rows[{{ $rowIndex }}][protocol_name]" class="table-selectized table-protocol-select w-full rounded-md border-gray-300" required>
                                                <option value=""></option>
                                                @foreach ($exp_protocols as $protocol)
                                                    <option value="{{ $protocol->name }}" @selected(($row['protocol_name'] ?? '') === $protocol->name)>
                                                        {{ $protocol->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="protocol_form_modal" title="Create new protocol">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 min-w-[260px]">
                                        <div class="flex items-center gap-2">
                                            <span class="table-status-icon table-pathogen-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
                                            <select name="table_rows[{{ $rowIndex }}][pathogen]" class="table-selectized table-pathogen-select w-full rounded-md border-gray-300" required>
                                                <option value=""></option>
                                                @foreach ($rowPathogenOptions as $pathogenName)
                                                    <option value="{{ $pathogenName }}" @selected(($row['pathogen'] ?? '') === $pathogenName)>
                                                        {{ $pathogenName }}
                                                    </option>
                                                @endforeach
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
                                        <select name="table_rows[{{ $rowIndex }}][outcome_type]" class="table-selectized table-outcome-type-select w-full rounded-md border-gray-300" required>
                                            @foreach (['Qualitative only', 'Both qualitative and quantitative'] as $option)
                                                <option value="{{ $option }}" @selected(($row['outcome_type'] ?? 'Qualitative only') === $option)>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-2 min-w-[220px]">
                                        <select name="table_rows[{{ $rowIndex }}][outcome_qual]" class="table-selectized w-full rounded-md border-gray-300" required>
                                            @foreach (['Strong positive', 'Positive', 'Suspect', 'Negative', 'Inconclusive', 'Unsuccessful', 'To be repeated'] as $option)
                                                <option value="{{ $option }}" @selected(($row['outcome_qual'] ?? 'Negative') === $option)>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-2 min-w-[180px]">
                                        <input type="number" step="any" name="table_rows[{{ $rowIndex }}][outcome_quant]" value="{{ $row['outcome_quant'] ?? '' }}" class="table-outcome-quant-input w-full rounded-md border-gray-300">
                                    </td>
                                    <td class="px-2 py-2 min-w-[180px]">
                                        <select name="table_rows[{{ $rowIndex }}][purpose]" class="table-selectized table-purpose-select w-full rounded-md border-gray-300" required>
                                            <option value=""></option>
                                            @foreach (\App\Enums\ExperimentPurpose::options() as $purposeValue => $purposeLabel)
                                                <option value="{{ $purposeValue }}" @selected(($row['purpose'] ?? '') === $purposeValue)>
                                                    {{ $purposeLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-2 py-2 min-w-[180px]">
                                        <input type="date" name="table_rows[{{ $rowIndex }}][date_tested]" value="{{ $row['date_tested'] ?? now()->toDateString() }}" class="w-full rounded-md border-gray-300" required>
                                    </td>
                                    <td class="px-2 py-2 min-w-[240px]">
                                        <div class="flex items-center gap-2">
                                            <span class="table-status-icon table-laboratory-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
                                            <select name="table_rows[{{ $rowIndex }}][laboratory]" class="table-selectized table-laboratory-select w-full rounded-md border-gray-300" required>
                                                <option value=""></option>
                                                @foreach ($laboratoryOptions as $laboratoryName)
                                                    <option value="{{ $laboratoryName }}" @selected(($row['laboratory'] ?? '') === $laboratoryName)>
                                                        {{ $laboratoryName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="laboratory_modal" title="Create new laboratory">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 min-w-[240px]">
                                        <div class="flex items-center gap-2">
                                            <span class="table-status-icon table-tester-status text-red-700"><i class="fa-solid fa-circle-xmark"></i></span>
                                            <select name="table_rows[{{ $rowIndex }}][tested_by]" class="table-selectized w-full rounded-md border-gray-300" required @if (!$can_assign_registrar) disabled @endif>
                                                <option value=""></option>
                                                @foreach ($people as $person)
                                                    @php
                                                        $personLabel = trim(($person->title ?? '') . ' ' . ($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                                                        $selectedTester = (string) ($row['tested_by'] ?? ($locked_registrar_people_id ?? ''));
                                                    @endphp
                                                    <option value="{{ $person->id }}" @selected($selectedTester === (string) $person->id)>
                                                        {{ $personLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if (!$can_assign_registrar)
                                                <input type="hidden" name="table_rows[{{ $rowIndex }}][tested_by]" value="{{ $locked_registrar_people_id }}">
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 min-w-[90px]">
                                        <button type="button" class="experiments-table-remove-row inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-500">
                    Use the + buttons to create missing protocols, pathogens, and laboratories, then select them in the row. Pathogen choices must match the selected protocol association.
                </p>
            </div>

            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                <x-forms.submit
                    class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                    <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Register Table Rows
                </x-forms.submit>
            </div>
        </div>
    </x-forms.form>
</div>

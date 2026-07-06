<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow" x-data="{ templateOpen: false }">
    @php
        $statusStyles = [
            'existing' => ['icon' => 'fa-link', 'class' => 'text-blue-700', 'label' => 'matching value'],
            'new' => ['icon' => 'fa-plus', 'class' => 'text-green-700', 'label' => 'new value'],
            'similar' => ['icon' => 'fa-triangle-exclamation', 'class' => 'text-yellow-700', 'label' => 'similar value'],
            'missing' => ['icon' => 'fa-circle-xmark', 'class' => 'text-red-700', 'label' => 'missing required value'],
        ];
    @endphp

    <div class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                    <i class="fa-solid fa-file-csv text-blue-600"></i>
                    Bulk import microplastics identifications (CSV)
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Upload a spreadsheet with the required column names. You will get a preview with row checks before importing.
                </p>
            </div>
        </div>
    </div>

    <div class="px-6 py-4">
        <div class="grid grid-cols-1 items-end gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Upload file</label>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls"
                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-gray-500">
                    CSV files exported with commas or semicolons are supported. In Excel: File → Save As → choose <span class="font-semibold">CSV</span> from the format dropdown.
                </p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">Sub-project</label>
                <select wire:model="sub_project_id"
                    class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">No sub-project</option>
                    @foreach ($subProjectOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" @click="templateOpen = true"
                class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                View template
            </button>

            <button type="button" wire:click="resetPreview"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Reset
            </button>

            <button type="button" wire:click="import" @disabled($status !== 'preview' || $hasBlockingIssues)
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                Import
            </button>
        </div>

        <div x-show="templateOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="templateOpen = false">
            <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10">
                <div class="flex items-start justify-between gap-6 border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Microplastics import template (CSV)</h4>
                        <p class="mt-1 text-sm text-gray-600">Ensure your CSV includes the required columns below. Header names are case-insensitive and may use spaces or underscores.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="downloadTemplate"
                            class="rounded-lg px-2 py-1 text-blue-700 hover:bg-blue-100 hover:text-blue-800"
                            title="Download template CSV" aria-label="Download template CSV">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <button type="button" class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                            @click="templateOpen = false">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="text-sm text-gray-700">
                            Required columns:
                            <div class="mt-1 font-mono text-xs text-gray-800">
                                protocol_name, mps_type, identification_date, laboratory, identified_by_email, and either tube_code or tube_alias
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                        <div class="mb-2 text-sm font-semibold text-gray-800">Rules</div>
                        <ul class="space-y-1 text-sm text-gray-700">
                            <li>- <span class="font-mono">tube_code</span> or <span class="font-mono">tube_alias</span> should point to an existing eligible source tube in project <span class="font-semibold">{{ $projectCode }}</span>; unresolved rows can be corrected in the preview.</li>
                            <li>- <span class="font-mono">protocol_name</span> must match an existing protocol.</li>
                            <li>- <span class="font-mono">mps_type</span> can match an existing microplastics type or create a new one.</li>
                            <li>- <span class="font-mono">identification_date</span> is required and must use <span class="font-mono">YYYY-MM-DD</span>.</li>
                            <li>- <span class="font-mono">laboratory</span> can be a new value and will be created if needed.</li>
                            <li>- <span class="font-mono">identified_by_email</span> must match an existing person email.</li>
                            <li>- Optional numeric columns: <span class="font-mono">sample_weight</span> (in grams), <span class="font-mono">r_coeff</span>, and <span class="font-mono">m_feret</span>.</li>
                        </ul>
                    </div>

                    <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4">
                        <div class="mb-2 text-sm font-semibold text-gray-800">Example row</div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">tube_code</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">tube_alias</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">protocol_name</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">mps_type</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">sample_weight</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">r_coeff</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">m_feret</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">identification_date</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">laboratory</th>
                                        <th class="px-3 py-2 text-left font-bold text-gray-600">identified_by_email</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr>
                                        <td class="px-3 py-2">A1A1-NA-001-1</td>
                                        <td class="px-3 py-2">MP-TUBE-001</td>
                                        <td class="px-3 py-2">Microplastics identification</td>
                                        <td class="px-3 py-2">Polyamide</td>
                                        <td class="px-3 py-2">2.4</td>
                                        <td class="px-3 py-2">0.8</td>
                                        <td class="px-3 py-2">156.2</td>
                                        <td class="px-3 py-2">2026-04-15</td>
                                        <td class="px-3 py-2">Central Lab</td>
                                        <td class="px-3 py-2">analyst@example.org</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 bg-white px-6 py-4">
                    <button type="button" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="templateOpen = false">
                        Close
                    </button>
                </div>
            </div>
        </div>

        @if ($errorsList && !$rows)
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="mb-1 font-semibold">Issues</div>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errorsList as $error)
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-700"></i>
                            <span>{{ $error }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($rows)
            <div class="mt-6 text-xs text-gray-600 inline-flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-link text-blue-700"></i> matching values</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-plus text-green-700"></i> new value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-yellow-700"></i> similar value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-circle-xmark text-red-700"></i> missing required value</span>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Line</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Tube code</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Tube alias</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Protocol</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">MPS type</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Weight (g)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">r</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Feret</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Identification date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Laboratory</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Identified by</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Issues</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($rows as $row)
                                @php
                                    $tubeStatus = $statusStyles[$row['tube_status'] ?? 'missing'];
                                    $protocolStatus = $statusStyles[$row['protocol_status'] ?? 'missing'];
                                    $typeStatus = $statusStyles[$row['mps_type_status'] ?? 'missing'];
                                    $labStatus = $statusStyles[$row['laboratory_status'] ?? 'missing'];
                                    $personStatus = $statusStyles[$row['identified_by_status'] ?? 'missing'];
                                    $similarTubeOptions = $row['field_warnings']['tube']['options'] ?? [];
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['line'] }}</td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="space-y-2">
                                            <div class="inline-flex items-center gap-2">
                                                <i class="fa-solid {{ $tubeStatus['icon'] }} {{ $tubeStatus['class'] }}"></i>
                                                @if (($row['tube_status'] ?? 'missing') === 'existing')
                                                    <span>{{ $row['tube_code'] }}</span>
                                                @else
                                                    <input type="text" value="{{ $row['tube_code'] }}"
                                                        wire:change="applySuggestedValue({{ (int) $row['line'] }}, 'tube_code', $event.target.value)"
                                                        placeholder="Enter tube code"
                                                        class="w-44 rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500" />
                                                @endif
                                            </div>

                                            @if (!empty($row['resolved_tube_label']))
                                                <div class="text-xs text-green-700">{{ $row['resolved_tube_label'] }}</div>
                                            @endif

                                            @if (($row['tube_status'] ?? 'missing') !== 'existing')
                                                <select
                                                    wire:change="selectTubeForRow({{ (int) $row['line'] }}, $event.target.value)"
                                                    class="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">Choose existing tube</option>
                                                    @foreach ($eligibleTubeOptions as $tubeOption)
                                                        <option value="{{ $tubeOption['id'] }}"
                                                            @selected((int) ($row['tube_id'] ?? 0) === (int) $tubeOption['id'])>
                                                            {{ $tubeOption['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif

                                            @if ($similarTubeOptions !== [])
                                                <div class="space-y-1">
                                                    @foreach ($similarTubeOptions as $option)
                                                        <button type="button"
                                                            wire:click="selectTubeForRow({{ (int) $row['line'] }}, '{{ $option['id'] }}')"
                                                            class="inline-flex items-center gap-1 rounded-full border border-yellow-300 bg-yellow-50 px-2 py-1 text-[11px] font-medium text-yellow-800 hover:bg-yellow-100">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                            <span>Use "{{ $option['label'] }}"</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if (($row['tube_status'] ?? 'missing') === 'existing')
                                            <span class="text-gray-700">{{ $row['tube_alias'] ?: 'N/A' }}</span>
                                        @else
                                            <input type="text" value="{{ $row['tube_alias'] }}"
                                                wire:change="applySuggestedValue({{ (int) $row['line'] }}, 'tube_alias', $event.target.value)"
                                                placeholder="Enter tube alias"
                                                class="w-40 rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="inline-flex items-center gap-2">
                                            <i class="fa-solid {{ $protocolStatus['icon'] }} {{ $protocolStatus['class'] }}"></i>
                                            <span>{{ $row['protocol_name'] }}</span>
                                            @if (($row['protocol_status'] ?? 'missing') !== 'existing')
                                                <button type="button"
                                                    onclick="document.getElementById('microplastics_protocol_form_modal')?.classList.remove('hidden'); document.getElementById('microplastics_protocol_form_modal')?.classList.add('flex');"
                                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-green-700 hover:bg-green-200"
                                                    title="Create microplastics protocol" aria-label="Create microplastics protocol">
                                                    <i class="fa-solid fa-plus text-xs"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <i class="fa-solid {{ $typeStatus['icon'] }} {{ $typeStatus['class'] }}"></i>
                                            <span>{{ $row['mps_type'] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['sample_weight'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['r_coeff'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $row['m_feret'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        <input type="date" value="{{ $row['identification_date'] ?? '' }}"
                                            wire:change="applySuggestedValue({{ (int) $row['line'] }}, 'identification_date', $event.target.value)"
                                            class="w-full rounded-lg border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <i class="fa-solid {{ $labStatus['icon'] }} {{ $labStatus['class'] }}"></i>
                                            <span>{{ $row['laboratory'] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <i class="fa-solid {{ $personStatus['icon'] }} {{ $personStatus['class'] }}"></i>
                                            <span>{{ $row['identified_by_email'] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 align-top">
                                        @if (!empty($row['issues']) || !empty($row['warnings']))
                                            <div class="space-y-1">
                                                @foreach (($row['issues'] ?? []) as $issue)
                                                    <div class="flex items-start gap-2 text-red-700">
                                                        <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                                                        <span>{{ $issue }}</span>
                                                    </div>
                                                @endforeach
                                                @foreach (($row['warnings'] ?? []) as $warning)
                                                    <div class="flex items-start gap-2 text-yellow-700">
                                                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                                        <span>{{ $warning }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-green-700">Ready</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

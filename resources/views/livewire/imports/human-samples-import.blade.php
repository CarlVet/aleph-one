<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow" x-data="{ templateOpen: false }"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
    <div class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-csv text-blue-600"></i>
                    Bulk import human samples (CSV)
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Upload a CSV, preview and fix issues, then import in bulk.
                </p>
            </div>
        </div>
    </div>

    <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Upload file</label>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls"
                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-gray-500">
                    Upload a CSV or Excel file (<span class="font-semibold">.csv</span>, <span class="font-semibold">.xlsx</span>).
                </p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="button" @click="templateOpen = true"
                    class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                    View template
                </button>

                <button type="button" wire:click="resetPreview"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Reset
                </button>

                <button type="button" wire:click="import" @disabled($status !== 'preview')
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Import
                </button>
            </div>
        </div>

        <div x-show="templateOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="templateOpen = false">
            <div class="flex h-[95vh] w-full max-w-[98vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10">
                <div class="flex items-start justify-between gap-6 border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Human samples import template (CSV)</h4>
                        <p class="mt-1 text-sm text-gray-600">
                            Ensure your CSV includes the required columns below.
                        </p>
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

                <div class="flex-1 min-h-0 overflow-hidden px-6 py-5 pb-6">
                    @php
                        $columns = $template['columns'] ?? [];
                    @endphp
                    <div class="flex h-full min-h-0 flex-col gap-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-6">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Interactive template</div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        Hover the <span class="font-semibold">column name</span> to see what’s required and formatting rules.
                                        Hover the <span class="font-semibold">value</span> to see existing options in the database and whether new values can be created.
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Preferred header names are shown; common aliases are accepted.
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <div class="text-xs font-semibold text-gray-700 mb-2">Legend</div>
                                    <div class="flex flex-col gap-2 text-xs">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                                            <span class="text-gray-700">Required</span>
                                        </div>
                                        <div class="inline-flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                            <span class="text-gray-700">Conditional</span>
                                        </div>
                                        <div class="inline-flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
                                            <span class="text-gray-700">Optional</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 rounded-xl border border-gray-200 bg-white">
                            <div class="h-full overflow-auto">
                                <table class="min-w-max text-sm">
                                    <thead class="bg-indigo-50">
                                        <tr>
                                            @foreach ($columns as $col)
                                                @php
                                                    $required = $col['required'] ?? 'optional';
                                                    $dot = $required === 'required'
                                                        ? 'bg-red-500'
                                                        : ($required === 'conditional' ? 'bg-amber-500' : 'bg-gray-300');
                                                    $aliases = (array) ($col['aliases'] ?? []);
                                                    $aliasPreview = array_slice($aliases, 0, 6);
                                                    $aliasMore = max(0, count($aliases) - count($aliasPreview));
                                                    $accepted = (array) ($col['accepted'] ?? []);
                                                    $aliasText = implode(', ', $aliasPreview).($aliasMore > 0 ? ', +'.$aliasMore.' more' : '');
                                                @endphp
                                                <th class="px-4 py-4 text-left align-top">
                                                    <div class="relative group">
                                                        <div class="inline-flex items-start gap-2">
                                                            <span class="mt-1 h-2 w-2 rounded-full {{ $dot }}"></span>
                                                            <div class="min-w-[260px]">
                                                                <div class="inline-flex items-center gap-2">
                                                                    <span class="font-mono text-[13px] font-semibold text-indigo-950">{{ $col['header'] }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="absolute left-0 top-full z-50 mt-0 hidden w-[34rem] max-w-[calc(98vw-4rem)] pt-2 group-hover:block group-focus-within:block pointer-events-auto">
                                                            <div class="max-h-[70vh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                                                                <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                                                                    <div class="text-base font-bold text-indigo-950">{{ $col['header'] }}</div>
                                                                    <div class="mt-1 text-sm text-indigo-800">{{ $col['description'] }}</div>
                                                                </div>

                                                                <div class="px-5 py-4 overflow-auto">
                                                                    <div class="grid grid-cols-1 gap-3 text-sm">
                                                                        <div>
                                                                            <div class="text-xs font-bold uppercase tracking-wide text-indigo-700">Format</div>
                                                                            <div class="mt-1 text-gray-700">{{ $col['format'] }}</div>
                                                                        </div>

                                                                        @if (!empty($accepted))
                                                                            <div>
                                                                                <div class="text-xs font-bold uppercase tracking-wide text-indigo-700">Accepted values</div>
                                                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                                                    @foreach ($accepted as $v)
                                                                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-700">{{ $v }}</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endif

                                                                        <div>
                                                                            <div class="text-xs font-bold uppercase tracking-wide text-indigo-700">Create/link behavior</div>
                                                                            <div class="mt-1 text-gray-700">{{ $col['create_policy'] }}</div>
                                                                            @if (($col['create_notes'] ?? '') !== '')
                                                                                <div class="mt-1 text-gray-600">{{ $col['create_notes'] }}</div>
                                                                            @endif
                                                                        </div>

                                                                        <div>
                                                                            <div class="text-xs font-bold uppercase tracking-wide text-indigo-700">Accepted header aliases</div>
                                                                            <div class="mt-2 font-mono text-[11px] text-gray-700">
                                                                                {{ $aliasText }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr>
                                            @foreach ($columns as $col)
                                                @php
                                                    $values = (array) ($col['options'] ?? []);
                                                    $total = (int) ($col['options_total'] ?? 0);
                                                    $more = max(0, $total - count($values));
                                                @endphp
                                                <td class="px-4 py-4 align-top">
                                                    <div class="relative group">
                                                        <div class="min-w-[260px] max-w-[320px] whitespace-normal break-words text-gray-800">
                                                            {{ ($col['example'] ?? '') !== '' ? $col['example'] : '—' }}
                                                        </div>

                                                        <div class="absolute left-0 top-full z-40 mt-0 hidden w-[34rem] max-w-[calc(98vw-4rem)] pt-2 group-hover:block group-focus-within:block pointer-events-auto">
                                                            <div class="max-h-[70vh] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                                                                <div class="border-b border-indigo-100 bg-indigo-50 px-5 py-4">
                                                                    <div class="text-base font-bold text-indigo-950">Existing values</div>
                                                                    <div class="mt-1 text-sm text-indigo-800">for <span class="font-mono">{{ $col['header'] }}</span></div>
                                                                </div>
                                                                <div class="px-5 py-4 overflow-auto">
                                                                    <div class="text-sm text-gray-700">
                                                                        {{ $col['create_policy'] }}
                                                                    </div>

                                                                    @if (!empty($values))
                                                                        <div class="mt-4 text-xs font-bold uppercase tracking-wider text-indigo-700">
                                                                            Existing values (showing {{ count($values) }} of {{ $total }})
                                                                        </div>
                                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                                            @foreach ($values as $v)
                                                                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-800">{{ $v }}</span>
                                                                            @endforeach
                                                                            @if ($more > 0)
                                                                                <button
                                                                                    type="button"
                                                                                    wire:click="openTemplateOptions('{{ $col['field'] }}')"
                                                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-200"
                                                                                >
                                                                                    +{{ $more }} more
                                                                                </button>
                                                                            @endif
                                                                        </div>
                                                                    @else
                                                                        <div class="mt-4 text-sm text-gray-600">
                                                                            No fixed option list for this column.
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 bg-white px-6 py-4 flex justify-end">
                    <button type="button" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="templateOpen = false">
                        Close
                    </button>
                </div>
            </div>
        </div>

        @if (!empty($globalIssues))
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Issues</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($globalIssues as $issue)
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-xmark mt-0.5 text-red-700"></i>
                            <span>{{ $issue }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($globalWarnings))
            <div class="mt-5 rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                <div class="font-semibold mb-1">Warnings</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($globalWarnings as $warning)
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-yellow-700"></i>
                            <span>{{ $warning }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($status === 'preview')
            @php($pageData = $this->previewPage())

            <div class="mt-6 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ $pageData['from'] }}</span>-<span class="font-semibold text-gray-900">{{ $pageData['to'] }}</span>
                    of <span class="font-semibold text-gray-900">{{ $pageData['total'] }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600">Rows/page</label>
                    <select wire:model.live="perPage"
                        class="rounded-xl border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('page', max(1, $page - 1))"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Prev
                        </button>
                        <span class="text-sm text-gray-600">Page {{ $page }}</span>
                        <button type="button"
                            wire:click="$set('page', ($pageData['to'] < $pageData['total']) ? $page + 1 : $page)"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-2 text-xs text-gray-600 inline-flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-link text-blue-700"></i> matching values</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-plus text-green-700"></i> new value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-yellow-700"></i> similar value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-circle-xmark text-red-700"></i> missing required value</span>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Patient first name</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Patient last name</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Patient sex</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Patient DOB</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Patient country</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Sample type</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Field label</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Purpose</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Sampling site</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Area / Lat / Lon</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Collector</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Storage state</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($pageData['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['human_first_name']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['human_first_name']) || !empty($row['field_warnings']['human_name']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['human_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['human_first_name'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['human_first_name']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.human_first_name', null) !== null)
                                            <input type="text"
                                                wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.human_first_name"
                                                placeholder="Patient first name"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                        @endif
                                        @if (!empty($row['field_warnings']['human_first_name']))
                                            @foreach (($row['field_warnings']['human_first_name']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'human_first_name', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['field_label']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                                <span>-</span>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                                <span>{{ $row['field_label'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['human_last_name']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['human_last_name']) || !empty($row['field_warnings']['human_name']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['human_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['human_last_name'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['human_last_name']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.human_last_name', null) !== null)
                                            <input type="text"
                                                wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.human_last_name"
                                                placeholder="Patient last name"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                        @endif
                                        @if (!empty($row['field_warnings']['human_last_name']))
                                            @foreach (($row['field_warnings']['human_last_name']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'human_last_name', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['human_sex']) || !in_array($row['human_sex'], ['Male', 'Female']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @else
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @endif
                                            <span>{{ $row['human_sex'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['human_sex']) || !in_array($row['human_sex'], ['Male', 'Female']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.human_sex', null) !== null)
                                            <select wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.human_sex"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                <option value="">Patient sex</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <span>{{ $row['human_date_of_birth'] ?: '-' }}</span>
                                        @if (empty($row['human_date_of_birth']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.human_date_of_birth', null) !== null)
                                            <input type="date"
                                                wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.human_date_of_birth"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['human_country']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['human_country']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['human_country_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['human_country'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['human_country']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.human_country', null) !== null)
                                            <input type="text"
                                                wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.human_country"
                                                placeholder="Patient country"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                        @endif
                                        @if (!empty($row['field_warnings']['human_country']))
                                            @foreach (($row['field_warnings']['human_country']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'human_country', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['sample_type']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['sample_type']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['sample_type_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['sample_type'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['sample_type_exists']))
                                            <div class="mt-1 inline-flex w-full items-center gap-2">
                                                @if (empty($row['sample_type_category']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @endif
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.sample_type_category"
                                                    class="w-full min-w-[13rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select category</option>
                                                    <option value="host_derived">Host derived</option>
                                                    <option value="non_host_derived">Non host derived</option>
                                                </select>
                                            </div>
                                        @endif
                                        @if (!empty($row['field_warnings']['sample_type']))
                                            @foreach (($row['field_warnings']['sample_type']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'sample_type', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['date_collected']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $row['date_collected']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @else
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @endif
                                            <span>{{ $row['date_collected'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['date_collected']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $row['date_collected']) || data_get($rowOverrides, ((int) ($row['row_number'] ?? 0)).'.date_collected', null) !== null)
                                            <input type="date"
                                                wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.date_collected"
                                                class="mt-1 w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (empty($row['sampling_purpose']) || !empty($row['sampling_purpose_invalid']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.sampling_purpose"
                                                    class="w-full min-w-[11rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select purpose</option>
                                                    <option value="Diagnostic">Diagnostic</option>
                                                    <option value="Research">Research</option>
                                                    <option value="Surveillance">Surveillance</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['sampling_purpose'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['sampling_site']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['sampling_site']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['sampling_site_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['sampling_site'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['sampling_site_exists']))
                                            <div class="mt-1 inline-flex w-full items-center gap-2">
                                                @if (empty($row['sampling_site_country']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @elseif (!empty($row['field_warnings']['sampling_site_country']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['sampling_site_country_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.sampling_site_country"
                                                    placeholder="Country for new sampling site"
                                                    class="w-full min-w-[13rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                            @if (!empty($row['field_warnings']['sampling_site_country']))
                                                @foreach (($row['field_warnings']['sampling_site_country']['options'] ?? []) as $option)
                                                    <button type="button"
                                                        wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'sampling_site_country', @js($option))"
                                                        class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                        Use "{{ $option }}"
                                                    </button>
                                                @endforeach
                                            @endif
                                        @endif
                                        @if (!empty($row['field_warnings']['sampling_site']))
                                            @foreach (($row['field_warnings']['sampling_site']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'sampling_site', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div>{{ $row['area'] ?: '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['latitude'] ?: '-' }} / {{ $row['longitude'] ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['location']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['location']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['location_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['location'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['location_exists']))
                                            <div class="mt-1 inline-flex w-full items-center gap-2">
                                                @if (empty($row['location_lab']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @elseif (!empty($row['field_warnings']['location_lab']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['location_lab_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.location_lab"
                                                    placeholder="Laboratory for new location"
                                                    class="w-full min-w-[13rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                            @if (!empty($row['field_warnings']['location_lab']))
                                                @foreach (($row['field_warnings']['location_lab']['options'] ?? []) as $option)
                                                    <button type="button"
                                                        wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'location_lab', @js($option))"
                                                        class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                        Use "{{ $option }}"
                                                    </button>
                                                @endforeach
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['collector_email']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['collector_email']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['collector_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['collector_email'] ?: '-' }}</span>
                                        </div>
                                        @if (empty($row['collector_exists']) && !empty($row['collector_email']))
                                            <div class="mt-1 flex flex-col gap-1">
                                                <div class="inline-flex w-full items-center gap-2">
                                                    @if (empty($row['collector_first_name']))
                                                        <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                    @elseif (!empty($row['field_warnings']['collector_first_name']))
                                                        <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                    @elseif (!empty($row['collector_first_name_exists']))
                                                        <i class="fa-solid fa-link text-blue-700"></i>
                                                    @else
                                                        <i class="fa-solid fa-plus text-green-700"></i>
                                                    @endif
                                                    <input type="text"
                                                        wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.collector_first_name"
                                                        placeholder="First name"
                                                        class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                                </div>
                                                <div class="inline-flex w-full items-center gap-2">
                                                    @if (empty($row['collector_last_name']))
                                                        <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                    @elseif (!empty($row['field_warnings']['collector_last_name']))
                                                        <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                    @elseif (!empty($row['collector_last_name_exists']))
                                                        <i class="fa-solid fa-link text-blue-700"></i>
                                                    @else
                                                        <i class="fa-solid fa-plus text-green-700"></i>
                                                    @endif
                                                    <input type="text"
                                                        wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.collector_last_name"
                                                        placeholder="Last name"
                                                        class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                                </div>
                                                @if (!empty($row['field_warnings']['collector_first_name']))
                                                    @foreach (($row['field_warnings']['collector_first_name']['options'] ?? []) as $option)
                                                        <button type="button"
                                                            wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'collector_first_name', @js($option))"
                                                            class="mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                            Use first name "{{ $option }}"
                                                        </button>
                                                    @endforeach
                                                @endif
                                                @if (!empty($row['field_warnings']['collector_last_name']))
                                                    @foreach (($row['field_warnings']['collector_last_name']['options'] ?? []) as $option)
                                                        <button type="button"
                                                            wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'collector_last_name', @js($option))"
                                                            class="mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                            Use last name "{{ $option }}"
                                                        </button>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['storage_state_invalid']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.storage_state"
                                                    class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Empty</option>
                                                    <option value="No preservative">No preservative</option>
                                                    <option value="Formalin">Formalin</option>
                                                    <option value="RNAlater">RNAlater</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['storage_state'] ?: '-' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($status === 'imported')
            <div class="mt-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                Import completed. Refresh the page if you do not see the new records immediately.
            </div>
        @endif
    </div>
</div>

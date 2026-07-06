<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow" x-data="{ templateOpen: false }"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
    <div class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-csv text-blue-600"></i>
                    Bulk import parasite samples (CSV)
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
                        <h4 class="text-lg font-semibold text-gray-900">Parasite samples import template (CSV)</h4>
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
            <div class="mt-1 text-xs text-gray-500">
                Row photo upload formats: JPG, JPEG, PNG, WEBP, PDF. Max size: 50MB.
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Origin type</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Origin code</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Parasite species</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Family (new species)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Stage</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Sex</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Repletion</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Identified at</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Identified by</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Sample state</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Photo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($pageData['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (empty($row['origin_type']) || !in_array($row['origin_type'], ['human', 'animal', 'environment']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.origin_type"
                                                    class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select origin type</option>
                                                    <option value="human">human</option>
                                                    <option value="animal">animal</option>
                                                    <option value="environment">environment</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['origin_type'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['origin_code']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['origin_code']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['origin_code_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['origin_code'] ?: '-' }}</span>
                                        </div>
                                        @if (!empty($row['field_warnings']['origin_code']))
                                            @foreach (($row['field_warnings']['origin_code']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'origin_code', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="inline-flex items-center gap-1">
                                            @if (empty($row['parasite_species']))
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                            @elseif (!empty($row['field_warnings']['parasite_species']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @elseif (!empty($row['parasite_species_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['parasite_species'] ?: '-' }}</span>
                                        </div>
                                        @if (!empty($row['field_warnings']['parasite_species']))
                                            @foreach (($row['field_warnings']['parasite_species']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'parasite_species', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (empty($row['parasite_species_exists']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                @if (empty($row['parasite_family']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.parasite_family"
                                                    placeholder="Family for new species"
                                                    class="w-full min-w-[11rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['stage_invalid']) || empty($row['stage']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.stage"
                                                    class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select stage</option>
                                                    <option value="Egg">Egg</option>
                                                    <option value="Larva">Larva</option>
                                                    <option value="Pupa">Pupa</option>
                                                    <option value="Nymph">Nymph</option>
                                                    <option value="Adult">Adult</option>
                                                    <option value="N/A">N/A</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['stage'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['sex_invalid']) || empty($row['sex']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.sex"
                                                    class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select sex</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="N/A">N/A</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['sex'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['repletion_state_invalid']) || empty($row['repletion_state']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <select
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.repletion_state"
                                                    class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs">
                                                    <option value="">Select repletion state</option>
                                                    <option value="Engorged">Engorged</option>
                                                    <option value="Partially engorged">Partially engorged</option>
                                                    <option value="Not engorged">Not engorged</option>
                                                    <option value="N/A">N/A</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $row['repletion_state'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['show_date_identified_input']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                @if (empty($row['date_identified']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @else
                                                    <i class="fa-solid fa-check text-green-700"></i>
                                                @endif
                                                <input type="date"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.date_identified"
                                                    class="w-full min-w-[10rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                        @else
                                            <span>{{ $row['date_identified'] ?: '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['show_identified_at_input']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                @if (empty($row['identified_at']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @elseif (!empty($row['field_warnings']['identified_at']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['identified_at_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.identified_at"
                                                    placeholder="Laboratory name"
                                                    class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1">
                                                @if (!empty($row['field_warnings']['identified_at']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['identified_at_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <span>{{ $row['identified_at'] ?: '-' }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($row['field_warnings']['identified_at']))
                                            @foreach (($row['field_warnings']['identified_at']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'identified_at', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['show_identified_by_input']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                @if (empty($row['identified_by']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @elseif (!empty($row['field_warnings']['identified_by']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['identified_by_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.identified_by"
                                                    placeholder="Full name"
                                                    class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1">
                                                @if (!empty($row['field_warnings']['identified_by']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['identified_by_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <span>{{ $row['identified_by'] ?: '-' }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($row['field_warnings']['identified_by']))
                                            @foreach (($row['field_warnings']['identified_by']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'identified_by', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        @if (!empty($row['show_sample_state_input']))
                                            <div class="inline-flex w-full items-center gap-2">
                                                @if (empty($row['sample_state']))
                                                    <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                @elseif (!empty($row['field_warnings']['sample_state']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['sample_state_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <input type="text"
                                                    wire:model.live="rowOverrides.{{ (int) ($row['row_number'] ?? 0) }}.sample_state"
                                                    placeholder="Tube sample state"
                                                    class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs" />
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1">
                                                @if (!empty($row['field_warnings']['sample_state']))
                                                    <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                @elseif (!empty($row['sample_state_exists']))
                                                    <i class="fa-solid fa-link text-blue-700"></i>
                                                @else
                                                    <i class="fa-solid fa-plus text-green-700"></i>
                                                @endif
                                                <span>{{ $row['sample_state'] ?: '-' }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($row['field_warnings']['sample_state']))
                                            @foreach (($row['field_warnings']['sample_state']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'sample_state', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <label class="inline-flex items-center gap-2 rounded border border-gray-200 px-2 py-1 text-xs">
                                            <i class="fa-solid fa-camera text-blue-600"></i>
                                            <span>Upload</span>
                                            <input type="file"
                                                wire:model="rowPhotos.{{ (int) ($row['row_number'] ?? 0) }}"
                                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                class="w-[11rem] text-xs" />
                                        </label>
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

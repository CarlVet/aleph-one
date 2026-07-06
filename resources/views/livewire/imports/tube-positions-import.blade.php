<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow" x-data="{ templateOpen: false }"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
    <div class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-cyan-50 px-6 py-4">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-csv text-blue-600"></i>
                    Bulk import tube positions (CSV)
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Upload a CSV, review row validations, then register tube positions.
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
                    Download and fill the template, then review row checks before importing.
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

                <button type="button" wire:click="import" @disabled($status !== 'preview' || ($previewPageData['has_blocking_issues'] ?? false))
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
                        <h4 class="text-lg font-semibold text-gray-900">Tube positions import template (CSV)</h4>
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
                                                                        <div class="mt-4 text-xs font-bold uppercase tracking-wide text-indigo-700">
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
            <div class="mt-2 text-xs text-gray-600 inline-flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-link text-blue-700"></i> matching values</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-plus text-green-700"></i> new value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-yellow-700"></i> similar value</span>
                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-circle-xmark text-red-700"></i> missing/invalid</span>
            </div>

            <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 md:flex-row md:items-center md:justify-between">
                <div>
                    Showing {{ $previewPageData['from'] }}-{{ $previewPageData['to'] }} of {{ $previewPageData['total'] }} rows
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rows per page</span>
                        <select wire:model.live="perPage"
                            class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </label>

                    <div class="inline-flex items-center gap-2">
                        <button type="button" wire:click="previousPage"
                            @disabled($previewPageData['current_page'] <= 1)
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
                            Previous
                        </button>

                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Page {{ $previewPageData['current_page'] }} / {{ $previewPageData['last_page'] }}
                        </span>

                        <button type="button" wire:click="nextPage"
                            @disabled($previewPageData['current_page'] >= $previewPageData['last_page'])
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Tube / Sample</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Box</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Position</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Date moved</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Moved by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($previewPageData['rows'] as $row)
                                <tr class="hover:bg-gray-50" wire:key="tube-position-preview-row-{{ (int) ($row['row_number'] ?? 0) }}">
                                    <td class="px-4 py-3 pr-10 text-sm text-gray-900 min-w-[22rem]">
                                        <div class="inline-flex items-center gap-1">
                                            @if (!empty($row['tube_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                                <span>{{ $row['selected_tube_code'] }}</span>
                                            @else
                                                <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                <span>{{ $row['tube_id'] ?: ($row['tube_alias'] ?: ($row['sample_code'] ?: '-')) }}</span>
                                            @endif
                                        </div>
                                        @if (!empty($row['selected_sample_code']))
                                            <div class="text-xs text-gray-500">Sample: {{ $row['selected_sample_code'] }}</div>
                                        @endif
                                        <div class="text-xs text-gray-500">Type: {{ $row['sample_type'] ?: '-' }}</div>
                                        @if (
                                            ($row['tube_alias'] ?? '') !== ''
                                            && ($row['sample_type'] ?? '') === 'Animal samples'
                                        )
                                            <div class="mt-1">
                                                <input type="text"
                                                    value="{{ $row['animal_sample_type'] ?? '' }}"
                                                    placeholder="animal_sample_type (e.g. Serum)"
                                                    class="w-full min-w-[16rem] rounded border border-gray-200 px-2 py-1 text-xs"
                                                    onblur="this.value = window.alephNormalizeNameStyle(this.value); this.dispatchEvent(new Event('input', { bubbles: true }))"
                                                    wire:input="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'animal_sample_type', $event.target.value)" />
                                                <div class="mt-1 text-[11px] text-gray-500">
                                                    Required for Animal samples when using <span class="font-mono">tube_alias</span> (alias can repeat across matrices).
                                                </div>
                                            </div>
                                        @endif
                                        @if (!empty($row['issues']))
                                            <div class="mt-2 text-xs text-red-700">
                                                {{ implode(' | ', array_slice((array) $row['issues'], 0, 2)) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 min-w-[18rem]">
                                        <div class="inline-flex items-center gap-1">
                                            @if (!empty($row['box_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                                @if (!empty($row['selected_box_code']))
                                                    <a href="/bank/boxes/{{ $row['box']->id ?? '' }}/contents" class="text-blue-700 hover:underline" target="_blank">
                                                        {{ $row['selected_box_code'] }}
                                                    </a>
                                                @else
                                                    <span>{{ $row['box_code'] ?: '-' }}</span>
                                                @endif
                                            @elseif (!empty($row['field_warnings']['box_code']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                                <span>{{ $row['box_code'] ?: '-' }}</span>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                                <span>{{ $row['box_code'] ?: '-' }}</span>
                                            @endif
                                        </div>
                                        @if (empty($row['box_exists']))
                                            <div class="mt-1 grid grid-cols-2 gap-2">
                                                <input type="text"
                                                    value="{{ $row['box_x'] }}"
                                                    placeholder="box_x (columns)"
                                                    class="w-full rounded border border-gray-200 px-2 py-1 text-xs"
                                                    wire:input="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'box_x', $event.target.value)" />
                                                <input type="text"
                                                    value="{{ $row['box_y'] }}"
                                                    placeholder="box_y (rows)"
                                                    class="w-full rounded border border-gray-200 px-2 py-1 text-xs"
                                                    wire:input="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'box_y', $event.target.value)" />
                                            </div>
                                            <div class="mt-1 text-[11px] text-gray-500">Required for new boxes (min 6 columns × 6 rows).</div>
                                        @endif
                                        @if (!empty($row['field_warnings']['box_code']))
                                            @foreach (($row['field_warnings']['box_code']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'box_code', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 min-w-[12rem]">
                                        <div>X: {{ $row['position_x'] ?: '-' }}</div>
                                        <div class="text-xs text-gray-600">Y: {{ $row['position_y'] ?: '-' }}</div>
                                        @if (!empty($row['position_replacement']))
                                            <div class="mt-1 inline-flex items-start gap-1 text-xs text-red-700">
                                                <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600"></i>
                                                <span>{{ $row['position_replacement']['text'] ?? '' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 min-w-[8rem]">{{ $row['date_moved'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 min-w-[14rem]">
                                        <div class="inline-flex items-center gap-1">
                                            @if (!empty($row['mover_exists']))
                                                <i class="fa-solid fa-link text-blue-700"></i>
                                            @elseif (!empty($row['field_warnings']['moved_by']))
                                                <i class="fa-solid fa-triangle-exclamation text-yellow-700"></i>
                                            @else
                                                <i class="fa-solid fa-plus text-green-700"></i>
                                            @endif
                                            <span>{{ $row['moved_by'] ?: '-' }}</span>
                                        </div>
                                        @if (!empty($row['mover_needs_names']))
                                            <div class="mt-1 grid grid-cols-1 gap-1" wire:key="mover-names-{{ (int) ($row['row_number'] ?? 0) }}">
                                                <div class="flex items-center gap-1">
                                                    @if (empty($row['moved_by_first_name']))
                                                        <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                    @endif
                                                    <input type="text"
                                                        value="{{ $row['moved_by_first_name'] ?? '' }}"
                                                        placeholder="moved_by_first_name (required)"
                                                        class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs"
                                                        wire:blur="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'moved_by_first_name', $event.target.value)" />
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    @if (empty($row['moved_by_last_name']))
                                                        <i class="fa-solid fa-circle-xmark text-red-700"></i>
                                                    @endif
                                                    <input type="text"
                                                        value="{{ $row['moved_by_last_name'] ?? '' }}"
                                                        placeholder="moved_by_last_name (required)"
                                                        class="w-full min-w-[12rem] rounded border border-gray-200 px-2 py-1 text-xs"
                                                        wire:blur="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'moved_by_last_name', $event.target.value)" />
                                                </div>
                                                <div class="text-[11px] text-gray-500">
                                                    Required for new mover emails. Edits are saved when you leave each field.
                                                </div>
                                            </div>
                                        @endif
                                        @if (!empty($row['field_warnings']['moved_by']))
                                            @foreach (($row['field_warnings']['moved_by']['options'] ?? []) as $option)
                                                <button type="button"
                                                    wire:click="applySuggestedValue({{ (int) ($row['row_number'] ?? 0) }}, 'moved_by', @js($option))"
                                                    class="mt-1 mr-2 text-[11px] text-yellow-900 underline hover:text-yellow-700">
                                                    Use "{{ $option }}"
                                                </button>
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($previewPageData['last_page'] > 1)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    @for ($pageNumber = $previewPageData['start_page']; $pageNumber <= $previewPageData['end_page']; $pageNumber++)
                        <button type="button" wire:click="goToPage({{ $pageNumber }})"
                            class="{{ $pageNumber === $previewPageData['current_page'] ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100' }} rounded-lg border px-3 py-1.5 text-sm font-medium">
                            {{ $pageNumber }}
                        </button>
                    @endfor
                </div>
            @endif
        @endif
    </div>

    <script>
        window.alephNormalizeNameStyle = window.alephNormalizeNameStyle || function(value) {
            const text = String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
            if (text.length === 0) {
                return '';
            }

            return text.charAt(0).toUpperCase() + text.slice(1);
        };
    </script>
</div>


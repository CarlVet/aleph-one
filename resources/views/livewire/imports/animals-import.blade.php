<div class="mt-6 rounded-2xl border border-gray-200 bg-white shadow" x-data="{ templateOpen: false }"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
    <div class="border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-csv text-indigo-600"></i>
                    Bulk import animals (CSV)
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Upload a spreadsheet with the required column names. You will get a preview with errors before importing.
                </p>
            </div>
        </div>
    </div>

    <div class="px-6 py-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Upload file</label>
                <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls"
                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <p class="mt-1 text-xs text-gray-500">
                    Upload a CSV or Excel file (<span class="font-semibold">.csv</span>, <span class="font-semibold">.xlsx</span>).
                </p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="button" @click="templateOpen = true"
                    class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                    View template
                </button>

                <button type="button" wire:click="resetPreview"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Reset
                </button>

                <button type="button" wire:click="import" @disabled($status !== 'preview')
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Import
                </button>
            </div>
        </div>

        <!-- Template modal -->
        <div x-show="templateOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="templateOpen = false">
            <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10">
                <div class="flex items-start justify-between gap-6 border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Animals import template (CSV)</h4>
                        <p class="mt-1 text-sm text-gray-600">
                            Required columns: <span class="font-mono">animal_species, field_label, sex, age, owner_type</span>
                        </p>
                    </div>
                    <button type="button" class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                        @click="templateOpen = false">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Allowed values</div>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>- <span class="font-mono">sex</span>: <span class="font-semibold">Male</span>, <span class="font-semibold">Female</span>, <span class="font-semibold">NA</span></li>
                                <li>- <span class="font-mono">age</span>: <span class="font-semibold">Juvenile</span>, <span class="font-semibold">Sub-adult</span>, <span class="font-semibold">Adult</span>, <span class="font-semibold">NA</span></li>
                                <li>- <span class="font-mono">owner_type</span>: <span class="font-semibold">individual</span> or <span class="font-semibold">organization</span></li>
                            </ul>
                            <div class="mt-3 text-sm text-gray-600">
                                If <span class="font-mono">owner_type=individual</span> then include <span class="font-mono">owner_human_code</span> (must exist in this project).
                                If <span class="font-mono">owner_type=organization</span> then include <span class="font-mono">owner_organization_name</span> and (if new) <span class="font-mono">owner_organization_country</span>.
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Example rows</div>
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">animal_species</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">field_label</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">sex</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">age</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">owner_type</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">owner_human_code</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">owner_organization_name</th>
                                            <th class="px-3 py-2 text-left font-bold text-gray-600">owner_organization_country</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr>
                                            <td class="px-3 py-2">Lion</td>
                                            <td class="px-3 py-2">KNP001</td>
                                            <td class="px-3 py-2">Male</td>
                                            <td class="px-3 py-2">Adult</td>
                                            <td class="px-3 py-2">individual</td>
                                            <td class="px-3 py-2">A1A1-HU-4</td>
                                            <td class="px-3 py-2"></td>
                                            <td class="px-3 py-2"></td>
                                        </tr>
                                        <tr>
                                            <td class="px-3 py-2">Elephant</td>
                                            <td class="px-3 py-2">KNP002</td>
                                            <td class="px-3 py-2">Female</td>
                                            <td class="px-3 py-2">Sub-adult</td>
                                            <td class="px-3 py-2">organization</td>
                                            <td class="px-3 py-2"></td>
                                            <td class="px-3 py-2">Kruger Veterinary Unit</td>
                                            <td class="px-3 py-2">South Africa</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                Tip: keep header names exactly as above.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 bg-white px-6 py-4 flex justify-end">
                    <button type="button" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
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
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($status === 'preview')
            @php($pageData = $this->previewPage())

            <div class="mt-6 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ $pageData['from'] }}</span>–<span class="font-semibold text-gray-900">{{ $pageData['to'] }}</span>
                    of <span class="font-semibold text-gray-900">{{ $pageData['total'] }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm text-gray-600">Rows/page</label>
                    <select wire:model.live="perPage"
                        class="rounded-xl border-gray-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Species</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Field label</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Sex</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Age</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Issues</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($pageData['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $row['animal_species'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $row['field_label'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $row['sex'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $row['age'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @if ($row['owner_type'] === 'individual')
                                            <span class="font-semibold">Human</span>: {{ $row['owner_human_code'] ?: '—' }}
                                        @elseif ($row['owner_type'] === 'organization')
                                            <span class="font-semibold">Org</span>: {{ $row['owner_organization_name'] ?: '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if (!empty($row['issues']))
                                            <ul class="list-disc pl-5 text-red-700 space-y-1">
                                                @foreach ($row['issues'] as $issue)
                                                    <li>{{ $issue }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">OK</span>
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
                Import completed. Refresh the page if you don’t see the new records immediately.
            </div>
        @endif
    </div>
</div>


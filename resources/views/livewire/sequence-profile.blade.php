@if(!($canView ?? true))
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-center justify-center">
                    <div class="text-center max-w-md">
                        <div class="bg-red-100 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-red-900 mb-3">Access Denied</h2>
                        <p class="text-red-700 text-lg mb-6 leading-relaxed">{{ $unauthorizedMessage ?? 'You are not authorized to view this sequence.' }}</p>
                        <a href="/samples/nucleic/sequences/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
<div
    class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-data="{}"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
>
    @php
        $derivedNucleic = $sequence->nucleic_acids;
        $experiment = $derivedNucleic?->nucleic_content instanceof \App\Models\Experiments ? $derivedNucleic->nucleic_content : null;
        $originalNucleic = ($experiment && $experiment->experiments_content instanceof \App\Models\NucleicAcids) ? $experiment->experiments_content : $derivedNucleic;
        $source = $originalNucleic?->nucleic_content;
        $sourceType = $source ? class_basename($source) : 'N/A';
        $sourceCode = data_get($source, 'code') ?? 'N/A';
        $derivedNucleicCode = data_get($derivedNucleic, 'code');
        $originalNucleicCode = data_get($originalNucleic, 'code');
        $experimentCode = data_get($experiment, 'code');
        $protocolCode = data_get($experiment, 'protocols.code');
        $sourceUrl = match ($sourceType) {
            'HumanSamples' => $sourceCode !== 'N/A' ? '/samples/humans/'.$sourceCode : null,
            'AnimalSamples' => $sourceCode !== 'N/A' ? '/samples/animals/'.$sourceCode : null,
            'EnvironmentSamples' => $sourceCode !== 'N/A' ? '/samples/environment/'.$sourceCode : null,
            'ParasiteSamples' => $sourceCode !== 'N/A' ? '/samples/parasites/'.$sourceCode : null,
            'Cultures' => $sourceCode !== 'N/A' ? '/samples/cultures/'.$sourceCode : null,
            'Pools' => $sourceCode !== 'N/A' ? '/samples/pools/'.$sourceCode : null,
            'NucleicAcids' => $sourceCode !== 'N/A' ? '/samples/nucleic/'.$sourceCode : null,
            'Experiments' => $sourceCode !== 'N/A' ? '/experiments/'.$sourceCode : null,
            default => null,
        };
        $originalAliasCodes = collect(data_get($originalNucleic, 'tubes', []))
            ->pluck('alias_code')
            ->filter(fn ($alias) => is_string($alias) && trim($alias) !== '')
            ->map(fn ($alias) => trim($alias))
            ->unique()
            ->values()
            ->all();
        $originalAliasLabel = !empty($originalAliasCodes) ? implode(', ', $originalAliasCodes) : 'N/A';
        $sourceDetails = match (get_class($source)) {
            \App\Models\HumanSamples::class => [
                ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/humans/'.data_get($source, 'code') : null],
                ['label' => 'Sample type', 'value' => data_get($source, 'sample_types.name') ?? 'N/A'],
                ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
            ],
            \App\Models\AnimalSamples::class => [
                ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/animals/'.data_get($source, 'code') : null],
                ['label' => 'Species', 'value' => data_get($source, 'animals.animal_species.name_common') ?? data_get($source, 'animals.animal_species.name_scientific') ?? 'N/A'],
                ['label' => 'Sample type', 'value' => data_get($source, 'sample_types.name') ?? 'N/A'],
                ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
            ],
            \App\Models\EnvironmentSamples::class => [
                ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/environment/'.data_get($source, 'code') : null],
                ['label' => 'Env type', 'value' => data_get($source, 'environment_sample_types.name') ?? 'N/A'],
                ['label' => 'Area', 'value' => data_get($source, 'area') ?? 'N/A'],
                ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
            ],
            \App\Models\ParasiteSamples::class => [
                ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/parasites/'.data_get($source, 'code') : null],
                ['label' => 'Species', 'value' => data_get($source, 'parasites.parasite_species.name_scientific') ?? 'N/A'],
                ['label' => 'Sample type', 'value' => data_get($source, 'parasite_sample_types.name') ?? 'N/A'],
            ],
            \App\Models\Cultures::class => [
                ['label' => 'Culture code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/cultures/'.data_get($source, 'code') : null],
                ['label' => 'Medium', 'value' => data_get($source, 'medium') ?? 'N/A'],
                ['label' => 'Step', 'value' => data_get($source, 'step') ?? 'N/A'],
            ],
            \App\Models\Pools::class => [
                ['label' => 'Pool code', 'value' => data_get($source, 'code') ?? 'N/A', 'url' => data_get($source, 'code') ? '/samples/pools/'.data_get($source, 'code') : null],
                ['label' => 'Nr pooled', 'value' => data_get($source, 'nr_pooled') ?? 'N/A'],
                ['label' => 'Date pooled', 'value' => data_get($source, 'date_pooled') ? \Carbon\Carbon::parse(data_get($source, 'date_pooled'))->format('Y-m-d') : 'N/A'],
            ],
            default => [
                ['label' => 'Details', 'value' => 'N/A'],
            ],
        };
        $poolContentRows = $source instanceof \App\Models\Pools
            ? collect(data_get($source, 'pool_contents', []))->map(fn ($poolContent) => [
                'type' => class_basename((string) data_get($poolContent, 'samples_type')),
                'code' => data_get($poolContent, 'samples.code') ?? 'N/A',
            ])->all()
            : [];
    @endphp

    <div class="px-4 py-6 sm:px-0 space-y-6">
        @if (session('success'))
            <div id="sequenceProfileSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div id="sequenceProfileErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const successEl = document.getElementById('sequenceProfileSuccessMessage');
                const errorEl = document.getElementById('sequenceProfileErrorMessage');
                if (successEl && typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Success', text: successEl.textContent, timer: 2400, showConfirmButton: false });
                }
                if (errorEl && typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: errorEl.textContent, confirmButtonColor: '#d33' });
                }
            });
        </script>

        <div class="rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-800 p-6 shadow-lg">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-4">
                    <h1 class="text-3xl font-bold text-white">
                        Sequence {{ $sequence->code }}
                        @if (filled($sequence->accession_number))
                            <span class="text-2xl font-semibold text-emerald-100">
                                (
                                Acc. Nr.
                                <a
                                    href="https://www.ncbi.nlm.nih.gov/nuccore/{{ urlencode($sequence->accession_number) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="underline decoration-emerald-200/80 underline-offset-2 transition-colors duration-200 hover:text-white"
                                >
                                    {{ $sequence->accession_number }}
                                </a>
                                )
                            </span>
                        @endif
                    </h1>
                    <div class="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm text-emerald-50 lg:flex-nowrap">
                        <div class="whitespace-nowrap"><span class="font-semibold text-white">Length:</span> {{ number_format($sequence->length) }} nt</div>
                        <div class="whitespace-nowrap"><span class="font-semibold text-white">Method:</span> {{ $sequence->method ?? 'N/A' }}</div>
                        <div class="whitespace-nowrap"><span class="font-semibold text-white">Instrument:</span> {{ $sequence->instrument ?? 'N/A' }}</div>
                        <div class="whitespace-nowrap"><span class="font-semibold text-white">Date sequenced:</span> {{ $sequence->date_sequenced?->format('Y-m-d') ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <a href="/samples/nucleic/sequences/list"
                        class="inline-flex items-center rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-white/30">
                        Back to List
                    </a>
                    @if(($canEdit ?? false))
                        <button
                            wire:click="deleteSequence"
                            wire:confirm="Are you sure you want to delete this sequence? This action cannot be undone."
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-500"
                        >
                            Delete
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Scientist & Laboratory</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500">Scientist</div>
                        <div class="mt-1 flex items-center gap-2 text-gray-900">
                            <x-people-logo :person="$sequence->people" width="24" />
                            <a href="/profile/{{ $sequence->people->id }}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ trim(($sequence->people->title ?? '').' '.($sequence->people->first_name ?? '').' '.($sequence->people->last_name ?? '')) ?: 'N/A' }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-500">Sequenced at</div>
                        <div class="mt-1 font-medium text-gray-900">{{ $sequence->laboratories->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Document</h2>
                    <p class="mt-1 text-sm text-gray-600">Max file size: 50MB. Supported formats: FASTA (.fa/.fasta), FASTQ (.fq/.fastq).</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('sequences.file.upload', ['code' => $sequence->code]) }}"
                    enctype="multipart/form-data"
                    class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center"
                >
                    @csrf
                    <input
                        type="file"
                        id="sequence_file"
                        name="sequence_file"
                        accept=".fa,.fasta,.fq,.fastq,.txt"
                        class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700"
                        @if (! ($canEdit ?? false)) disabled @endif
                        @if ($canEdit ?? false) required @endif
                    >
                    @if (($canEdit ?? false))
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-green-700"
                        >
                            Upload
                        </button>
                    @else
                        <span class="text-sm text-gray-500">Read-only: you can’t upload files in this project.</span>
                    @endif
                </form>

                @if($sequence->fasta_path)
                    <div class="space-y-4">
                        <div class="rounded-lg bg-gray-50 p-4">
                            @if($fastaError)
                                <div class="mb-2 text-sm text-red-600">{{ $fastaError }}</div>
                            @elseif($fastaContent)
                                <pre class="overflow-x-auto whitespace-pre-wrap break-all rounded bg-white p-4 text-sm font-mono">{{ $fastaContent }}</pre>
                                @if($isTruncated)
                                    <div class="mt-2 text-xs italic text-gray-600">Content truncated to 1000 characters. Download the file for the full content.</div>
                                @endif
                            @else
                                <div class="text-sm text-yellow-600">No content available</div>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ Storage::url($sequence->fasta_path) }}"
                                download
                                class="inline-flex items-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-orange-700">
                                Download file
                            </a>
                            @if(($canEdit ?? false))
                                <form method="POST" action="{{ route('sequences.file.delete', ['code' => $sequence->code]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-700"
                                        onclick="return confirm('Are you sure you want to delete this file?')"
                                    >
                                        Delete file
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 py-10 text-center text-sm text-gray-600">
                        No FASTA file uploaded yet.
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Full Traceback Information</h2>

            <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                <div><span class="font-semibold text-gray-700">Derived nucleic acid:</span>
                    @if ($derivedNucleicCode)
                        <a href="/samples/nucleic/{{ $derivedNucleicCode }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $derivedNucleicCode }}</a>
                    @else
                        N/A
                    @endif
                </div>
                <div><span class="font-semibold text-gray-700">Original nucleic acid:</span>
                    @if ($originalNucleicCode)
                        <a href="/samples/nucleic/{{ $originalNucleicCode }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $originalNucleicCode }}</a>
                    @else
                        N/A
                    @endif
                </div>
                <div><span class="font-semibold text-gray-700">Original nucleic aliases:</span> {{ $originalAliasLabel }}</div>
                <div><span class="font-semibold text-gray-700">Original content type:</span> {{ $sourceType }}</div>
                <div><span class="font-semibold text-gray-700">Original content code:</span>
                    @if ($sourceUrl)
                        <a href="{{ $sourceUrl }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $sourceCode }}</a>
                    @else
                        {{ $sourceCode }}
                    @endif
                </div>
                <div><span class="font-semibold text-gray-700">Experiment:</span>
                    @if ($experimentCode)
                        <a href="/experiments/{{ $experimentCode }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $experimentCode }}</a>
                    @else
                        N/A
                    @endif
                </div>
                <div><span class="font-semibold text-gray-700">Protocol:</span>
                    @if ($protocolCode)
                        <a href="/protocols/{{ $protocolCode }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ data_get($experiment, 'protocols.name') ?? $protocolCode }}</a>
                    @else
                        {{ data_get($experiment, 'protocols.name') ?? 'N/A' }}
                    @endif
                </div>
                <div><span class="font-semibold text-gray-700">Pathogen:</span> {{ data_get($experiment, 'pathogens.species') ?? 'N/A' }}</div>
            </div>

            <div class="mt-5 overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sourceDetails as $row)
                            <tr>
                                <td class="w-44 bg-gray-50 px-3 py-2 font-semibold text-gray-700">{{ $row['label'] }}</td>
                                <td class="px-3 py-2 text-gray-800">
                                    @if (!empty($row['url']) && ($row['value'] ?? 'N/A') !== 'N/A')
                                        <a href="{{ $row['url'] }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $row['value'] }}</a>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (!empty($poolContentRows))
                <div class="mt-4 rounded-lg border border-gray-200 p-3">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">Pooled contents</div>
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-2 py-1 text-left font-semibold text-gray-600">Type</th>
                                <th class="px-2 py-1 text-left font-semibold text-gray-600">Code</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($poolContentRows as $poolRow)
                                <tr>
                                    <td class="px-2 py-1 text-gray-700">{{ $poolRow['type'] ?: 'N/A' }}</td>
                                    <td class="px-2 py-1 text-gray-800">
                                        @php
                                            $poolCodeUrl = match ($poolRow['type'] ?? '') {
                                                'HumanSamples' => '/samples/humans/'.$poolRow['code'],
                                                'AnimalSamples' => '/samples/animals/'.$poolRow['code'],
                                                'EnvironmentSamples' => '/samples/environment/'.$poolRow['code'],
                                                'ParasiteSamples' => '/samples/parasites/'.$poolRow['code'],
                                                'Cultures' => '/samples/cultures/'.$poolRow['code'],
                                                'Pools' => '/samples/pools/'.$poolRow['code'],
                                                'NucleicAcids' => '/samples/nucleic/'.$poolRow['code'],
                                                default => null,
                                            };
                                        @endphp
                                        @if ($poolCodeUrl && $poolRow['code'] !== 'N/A')
                                            <a href="{{ $poolCodeUrl }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $poolRow['code'] }}</a>
                                        @else
                                            {{ $poolRow['code'] }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endif
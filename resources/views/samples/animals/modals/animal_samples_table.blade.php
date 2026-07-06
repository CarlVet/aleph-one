<!-- Table content -->
<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Select Animal Samples</h3>
        <button id="confirm_sample_selection" 
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-check mr-2"></i>Confirm Selection
        </button>
    </div>
    
    <table id="animal_samples_table" class="table table-striped w-full">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="select_all_samples" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </th>
                <th>Sample Code</th>
                <th>Animal Code</th>
                <th>Field ID</th>
                <th>Species</th>
                <th>Sample Type</th>
                <th>Date Collected</th>
                <th>Park</th>
                <th>Collector</th>
                <th>Processed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($animal_samples as $sample)
            <tr wire:key="{{ $sample->id }}" class="hover:bg-gray-50">
                <td class="px-4 py-2">
                    <input type="checkbox" 
                           class="select-sample rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                           value="{{ $sample->id }}"
                           {{ $sample->processed ? 'disabled' : '' }}>
                </td>
                <td class="px-4 py-2 font-medium">{{ $sample->code }}</td>
                <td class="px-4 py-2">{{ $sample->animals->code ?? 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->animals->field_label ?? 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->animals->animal_species->name_common ?? 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->sample_types->name ?? 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->date_collected ? \Carbon\Carbon::parse($sample->date_collected)->format('Y-m-d') : 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->places->name ?? 'N/A' }}</td>
                <td class="px-4 py-2">{{ $sample->people->first_name . " " . $sample->people->last_name ?? 'N/A' }}</td>
                <td class="px-4 py-2">
                    @if($sample->processed)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>Processed
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
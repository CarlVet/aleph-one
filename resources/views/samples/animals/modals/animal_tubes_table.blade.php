<!-- Table content -->
<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Existing Animal Tubes</h3>
        <span class="text-sm text-gray-600">{{ count($animal_tubes) }} tube(s) found</span>
    </div>
    
    @if(count($animal_tubes) > 0)
        <table id="animal_tubes_table" class="table table-striped w-full">
            <thead>
                <tr>
                    <th>Tube Code</th>
                    <th>State</th>
                    <th>Sample Code</th>
                    <th>Animal Code</th>
                    <th>Field ID</th>
                    <th>Species</th>
                    <th>Sample Type</th>
                    <th>Date Collected</th>
                    <th>Park</th>
                    <th>Collector</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($animal_tubes as $tube)
                    <tr wire:key="{{ $tube->id }}" class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium">{{ $tube->code ?? 'N/A' }}</td>
                        <td class="px-4 py-2">
                            @if($tube->state)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $tube->state === 'Frozen' ? 'bg-blue-100 text-blue-800' : 
                                       ($tube->state === 'Fixed in Formalin' ? 'bg-red-100 text-red-800' : 
                                       'bg-gray-100 text-gray-800') }}">
                                    {{ $tube->state }}
                                </span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->code ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->animals->code ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->animals->field_label ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->animals->animal_species->name_common ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->sample_types->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->date_collected ? \Carbon\Carbon::parse($tube->tubes_content->date_collected)->format('Y-m-d') : 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->places->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $tube->tubes_content->people ? $tube->tubes_content->people->first_name . ' ' . $tube->tubes_content->people->last_name : 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="text-center py-8">
            <i class="fas fa-vial text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-600 text-lg">No animal tubes found</p>
            <p class="text-gray-500 text-sm mt-2">Process some animal samples to create tubes</p>
        </div>
    @endif
</div>

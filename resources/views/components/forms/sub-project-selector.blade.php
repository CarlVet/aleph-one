@props([
    'name' => 'sub_project_id',
    'label' => null,
    'options' => [],
    'selected' => null,
])

@php
    $options = collect($options);
    $projectId = (int) session('selected_project_id');
    $requiresSelection = \App\Support\SubProjectFlag::requiresSelection(auth()->user(), $projectId);
    $defaultSelection = $requiresSelection
        ? \App\Support\SubProjectFlag::defaultSelectionForUser(auth()->user(), $projectId)
        : null;
    $resolvedSelected = old($name, $selected ?? $defaultSelection);
    $resolvedLabel = $label ?? ($requiresSelection ? 'Sub-project*' : 'Sub-project flag (optional)');
@endphp

@if ($options->isNotEmpty())
    <x-forms.field :label="$resolvedLabel" :name="$name">
        @if ($requiresSelection)
            <x-forms.select-input id="{{ $name }}" name="{{ $name }}" required>
                @foreach ($options as $subProject)
                    <option value="{{ $subProject->id }}" @selected((string) $resolvedSelected === (string) $subProject->id)>
                        {{ $subProject->code }} — {{ $subProject->name }}
                    </option>
                @endforeach
            </x-forms.select-input>
        @else
            <x-forms.select-input id="{{ $name }}" name="{{ $name }}">
                <option value="">Main project (no sub-project)</option>
                @foreach ($options as $subProject)
                    <option value="{{ $subProject->id }}" @selected((string) $resolvedSelected === (string) $subProject->id)>
                        {{ $subProject->code }} — {{ $subProject->name }}
                    </option>
                @endforeach
            </x-forms.select-input>
        @endif

        @if ($requiresSelection)
            <p class="mt-1 text-xs text-slate-500">You can only register data within your assigned sub-projects. The first assigned sub-project is pre-selected.</p>
        @else
            <p class="mt-1 text-xs text-slate-500">Leave as main project unless you want to tag this record to a specific sub-project.</p>
        @endif
    </x-forms.field>
@endif

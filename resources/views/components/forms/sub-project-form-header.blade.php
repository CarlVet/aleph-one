@props([
    'name' => 'sub_project_id',
    'options' => [],
    'selected' => null,
])

@php
    $options = collect($options);
@endphp

@if ($options->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'mb-6 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-blue-50 to-sky-50 px-4 py-4 shadow-sm ring-1 ring-indigo-100/70']) }}>
        <div class="mx-auto flex max-w-xl flex-col gap-1">
            <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-indigo-700">
                <i class="fas fa-flag text-[10px]"></i>
                Sub-project scope
            </div>
            <x-forms.sub-project-selector :name="$name" :options="$options" :selected="$selected" />
        </div>
    </div>
@endif

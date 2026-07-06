<div class="grid grid-cols-1 gap-6">
    @foreach ($definition['fields'] as $fieldName => $field)
        @php
            $value = old($fieldName, $record?->{$fieldName});
            $isRequired = (bool) ($field['required'] ?? false);
        @endphp

        <div>
            <label class="block text-sm font-semibold text-gray-700">
                {{ $field['label'] }}
                @if ($isRequired)
                    <span class="text-red-500">*</span>
                @endif
            </label>

            @if (($field['type'] ?? 'text') === 'textarea')
                <textarea name="{{ $fieldName }}" rows="4"
                    class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $value }}</textarea>
            @elseif (($field['type'] ?? 'text') === 'select')
                <select name="{{ $fieldName }}"
                    class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @if (! $isRequired)
                        <option value="">Select an option</option>
                    @endif
                    @foreach (($selectOptions[$fieldName] ?? []) as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
            @else
                <input type="{{ in_array(($field['type'] ?? 'text'), ['number', 'decimal'], true) ? 'number' : 'text' }}"
                    name="{{ $fieldName }}" value="{{ $value }}"
                    @if (($field['type'] ?? 'text') === 'decimal') step="any" @endif
                    @if (in_array(($field['type'] ?? 'text'), ['number', 'decimal'], true) && isset($field['min'])) min="{{ $field['min'] }}" @endif
                    @if (in_array(($field['type'] ?? 'text'), ['number', 'decimal'], true) && isset($field['max'])) max="{{ $field['max'] }}" @endif
                    class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @endif

            @error($fieldName)
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
</div>

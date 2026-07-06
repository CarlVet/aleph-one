<?php

namespace App\Support;

use App\Models\AnimalSpecies;
use App\Models\ClinicalSigns;
use App\Models\Countries;
use App\Models\Departments;
use App\Models\EnvironmentSampleTypes;
use App\Models\Laboratories;
use App\Models\Lesions;
use App\Models\Organizations;
use App\Models\ParasiteSampleTypes;
use App\Models\Pathogens;
use App\Models\Protocols;
use App\Models\RiskFactors;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminLookupRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'animal-species' => [
                'title' => 'Animal species',
                'model' => AnimalSpecies::class,
                'label_column' => 'name_common',
                'search_columns' => ['name_common', 'name_scientific', 'genus', 'family', 'order'],
                'list_columns' => ['name_common', 'name_scientific', 'family', 'order'],
                'fields' => [
                    'name_common' => ['label' => 'Common name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'name_scientific' => ['label' => 'Scientific name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'genus' => ['label' => 'Genus', 'type' => 'text'],
                    'family' => ['label' => 'Family', 'type' => 'text'],
                    'order' => ['label' => 'Order', 'type' => 'text'],
                    'class' => ['label' => 'Class', 'type' => 'text'],
                    'phylum' => ['label' => 'Phylum', 'type' => 'text'],
                ],
                'usage_relations' => ['animals' => 'animals', 'meta_animals' => 'animal meta records'],
            ],
            'clinical-signs' => [
                'title' => 'Clinical signs',
                'model' => ClinicalSigns::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'description'],
                'list_columns' => ['name', 'description'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => ['meta_animals' => 'animal meta records', 'meta_humans' => 'human meta records'],
            ],
            'countries' => [
                'title' => 'Countries',
                'model' => Countries::class,
                'label_column' => 'name',
                'search_columns' => ['name'],
                'list_columns' => ['name'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                ],
                'usage_relations' => [
                    'humans' => 'humans',
                    'organizations' => 'organizations',
                    'laboratories' => 'laboratories',
                    'sampling_sites' => 'sampling sites',
                    'meta_animals' => 'animal meta records',
                ],
            ],
            'departments' => [
                'title' => 'Departments',
                'model' => Departments::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'department_type', 'building', 'description'],
                'list_columns' => ['name', 'organizations_id', 'department_type', 'building'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'organizations_id' => ['label' => 'Organization', 'type' => 'select', 'relation' => 'organizations', 'option_source' => Organizations::class, 'option_label' => 'name'],
                    'department_type' => ['label' => 'Department type', 'type' => 'text'],
                    'building' => ['label' => 'Building', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => ['people' => 'people'],
            ],
            'environment-sample-types' => [
                'title' => 'Environment sample types',
                'model' => EnvironmentSampleTypes::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'category', 'description'],
                'list_columns' => ['name', 'category', 'description'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'category' => ['label' => 'Category', 'type' => 'text', 'required' => true],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => ['environment_samples' => 'environment samples', 'meta_environments' => 'environment meta records'],
            ],
            'laboratories' => [
                'title' => 'Laboratories',
                'model' => Laboratories::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'region', 'city', 'address', 'lab_type', 'description', 'latitude', 'longitude'],
                'list_columns' => ['name', 'organizations_id', 'countries_id', 'lab_type', 'latitude', 'longitude'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'organizations_id' => ['label' => 'Organization', 'type' => 'select', 'relation' => 'organization', 'option_source' => Organizations::class, 'option_label' => 'name'],
                    'countries_id' => ['label' => 'Country', 'type' => 'select', 'relation' => 'countries', 'option_source' => Countries::class, 'option_label' => 'name'],
                    'region' => ['label' => 'Region', 'type' => 'text'],
                    'city' => ['label' => 'City', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'type' => 'text'],
                    'lab_type' => ['label' => 'Lab type', 'type' => 'text'],
                    'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'min' => -90, 'max' => 90],
                    'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'min' => -180, 'max' => 180],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => [
                    'locations' => 'locations',
                    'parasites' => 'parasites',
                    'nucleic_acids' => 'nucleic acids',
                    'sequences' => 'sequences',
                    'cultures' => 'cultures',
                    'pools' => 'pools',
                    'experiments' => 'experiments',
                ],
            ],
            'lesions' => [
                'title' => 'Lesions',
                'model' => Lesions::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'description'],
                'list_columns' => ['name', 'description'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => ['meta_animals' => 'animal meta records', 'meta_humans' => 'human meta records'],
            ],
            'organizations' => [
                'title' => 'Organizations',
                'model' => Organizations::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'type', 'region', 'city', 'address', 'website', 'description'],
                'list_columns' => ['name', 'type', 'countries_id', 'city'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'type' => ['label' => 'Type', 'type' => 'text', 'required' => true],
                    'countries_id' => ['label' => 'Country', 'type' => 'select', 'relation' => 'countries', 'option_source' => Countries::class, 'option_label' => 'name', 'required' => true],
                    'region' => ['label' => 'Region', 'type' => 'text'],
                    'city' => ['label' => 'City', 'type' => 'text'],
                    'address' => ['label' => 'Address', 'type' => 'text'],
                    'website' => ['label' => 'Website', 'type' => 'text'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => [
                    'sampling_sites' => 'sampling sites',
                    'laboratories' => 'laboratories',
                    'departments' => 'departments',
                    'animals' => 'animals',
                ],
            ],
            'parasite-sample-types' => [
                'title' => 'Parasite sample types',
                'model' => ParasiteSampleTypes::class,
                'label_column' => 'name',
                'search_columns' => ['name'],
                'list_columns' => ['name'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                ],
                'usage_relations' => ['parasite_samples' => 'parasite samples', 'meta_parasites' => 'parasite meta records'],
            ],
            'pathogens' => [
                'title' => 'Pathogens',
                'model' => Pathogens::class,
                'label_column' => 'species',
                'search_columns' => ['species', 'genus', 'family', 'order', 'class', 'phylum', 'kingdom', 'domain'],
                'list_columns' => ['species', 'genus', 'family', 'ncbi_tax_id'],
                'fields' => [
                    'ncbi_tax_id' => ['label' => 'NCBI tax ID', 'type' => 'number'],
                    'species' => ['label' => 'Species', 'type' => 'text', 'required' => true, 'unique' => true],
                    'genus' => ['label' => 'Genus', 'type' => 'text', 'required' => true],
                    'family' => ['label' => 'Family', 'type' => 'text', 'required' => true],
                    'order' => ['label' => 'Order', 'type' => 'text', 'required' => true],
                    'class' => ['label' => 'Class', 'type' => 'text', 'required' => true],
                    'phylum' => ['label' => 'Phylum', 'type' => 'text', 'required' => true],
                    'kingdom' => ['label' => 'Kingdom', 'type' => 'text', 'required' => true],
                    'domain' => ['label' => 'Domain', 'type' => 'text', 'required' => true],
                ],
                'usage_relations' => [
                    'experiments' => 'experiments',
                    'protocols' => 'protocol associations',
                    'meta_animals' => 'animal meta records',
                    'meta_humans' => 'human meta records',
                    'meta_environments' => 'environment meta records',
                    'meta_parasites' => 'parasite meta records',
                ],
            ],
            'protocols' => [
                'title' => 'Protocols',
                'model' => Protocols::class,
                'label_column' => 'name',
                'search_columns' => ['code', 'name', 'pdf_path'],
                'list_columns' => ['code', 'name', 'techniques_id', 'pdf_path'],
                'fields' => [
                    'code' => ['label' => 'Code', 'type' => 'text', 'required' => true, 'unique' => true],
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'techniques_id' => ['label' => 'Technique', 'type' => 'select', 'relation' => 'techniques', 'option_source' => Techniques::class, 'option_label' => 'name', 'required' => true],
                    'pdf_path' => ['label' => 'Document path', 'type' => 'text'],
                ],
                'usage_relations' => [
                    'experiments' => 'experiments',
                    'microplastics' => 'microplastics',
                    'pathogens' => 'target pathogens',
                    'studies' => 'studies',
                    'comments' => 'comments',
                ],
            ],
            'risk-factors' => [
                'title' => 'Risk factors',
                'model' => RiskFactors::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'description'],
                'list_columns' => ['name', 'description'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                ],
                'usage_relations' => [
                    'meta_animals' => 'animal meta records',
                    'meta_humans' => 'human meta records',
                    'meta_environments' => 'environment meta records',
                    'meta_parasites' => 'parasite meta records',
                ],
            ],
            'sampling-sites' => [
                'title' => 'Sampling sites',
                'model' => SamplingSites::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'site_type', 'region', 'city', 'province', 'description'],
                'list_columns' => ['name', 'site_type', 'countries_id', 'region', 'city', 'province', 'latitude', 'longitude', 'organizations_id'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'site_type' => ['label' => 'Site type', 'type' => 'text'],
                    'countries_id' => ['label' => 'Country', 'type' => 'select', 'relation' => 'countries', 'option_source' => Countries::class, 'option_label' => 'name'],
                    'region' => ['label' => 'Region', 'type' => 'text'],
                    'city' => ['label' => 'City', 'type' => 'text'],
                    'province' => ['label' => 'Province', 'type' => 'text'],
                    'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'min' => -90, 'max' => 90],
                    'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'min' => -180, 'max' => 180],
                    'organizations_id' => ['label' => 'Organization', 'type' => 'select', 'relation' => 'organization', 'option_source' => Organizations::class, 'option_label' => 'name'],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'max' => 1000],
                ],
                'usage_relations' => [
                    'animal_samples' => 'animal samples',
                    'human_samples' => 'human samples',
                    'environment_samples' => 'environment samples',
                ],
            ],
            'sample-types' => [
                'title' => 'Sample types',
                'model' => SampleTypes::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'category'],
                'list_columns' => ['name', 'category'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'category' => ['label' => 'Category', 'type' => 'select', 'enum' => ['host_derived' => 'Host derived', 'non_host_derived' => 'Non-host derived'], 'required' => true],
                ],
                'usage_relations' => [
                    'human_samples' => 'human samples',
                    'animal_samples' => 'animal samples',
                    'meta_animals' => 'animal meta records',
                    'meta_humans' => 'human meta records',
                ],
            ],
            'studies' => [
                'title' => 'Studies',
                'model' => Studies::class,
                'label_column' => 'title',
                'search_columns' => ['ref_key', 'title', 'study_design', 'doi'],
                'list_columns' => ['ref_key', 'title', 'publication_year', 'study_design'],
                'fields' => [
                    'ref_key' => ['label' => 'Reference key', 'type' => 'text', 'required' => true, 'unique' => true],
                    'title' => ['label' => 'Title', 'type' => 'text', 'required' => true],
                    'abstract' => ['label' => 'Abstract', 'type' => 'textarea'],
                    'publication_year' => ['label' => 'Publication year', 'type' => 'number', 'required' => true, 'min' => 1800, 'max' => (int) now()->year + 1],
                    'study_design' => ['label' => 'Study design', 'type' => 'text', 'required' => true],
                    'pdf_path' => ['label' => 'PDF path', 'type' => 'text'],
                    'risk_bias' => ['label' => 'Risk bias', 'type' => 'text'],
                    'sampling_strategy' => ['label' => 'Sampling strategy', 'type' => 'text'],
                    'doi' => ['label' => 'DOI', 'type' => 'text', 'unique' => true, 'nullable_unique' => true],
                ],
                'usage_relations' => [
                    'protocols' => 'protocol associations',
                    'meta_animals' => 'animal meta records',
                    'meta_humans' => 'human meta records',
                    'meta_environments' => 'environment meta records',
                    'meta_parasites' => 'parasite meta records',
                ],
            ],
            'techniques' => [
                'title' => 'Techniques',
                'model' => Techniques::class,
                'label_column' => 'name',
                'search_columns' => ['name', 'type'],
                'list_columns' => ['name', 'type'],
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'unique' => true],
                    'type' => ['label' => 'Type', 'type' => 'text', 'required' => true],
                ],
                'usage_relations' => [
                    'protocols' => 'protocols',
                    'meta_animals' => 'animal meta records',
                    'meta_humans' => 'human meta records',
                    'meta_environments' => 'environment meta records',
                    'meta_parasites' => 'parasite meta records',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(string $lookup): array
    {
        $definitions = self::all();

        abort_unless(isset($definitions[$lookup]), 404);

        $definition = $definitions[$lookup];
        $modelClass = $definition['model'];
        /** @var Model $model */
        $model = new $modelClass;
        $definition['table'] = $model->getTable();
        $definition['lookup'] = $lookup;

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    public static function selectOptions(string $lookup): array
    {
        $definition = self::get($lookup);
        $options = [];

        foreach ($definition['fields'] as $name => $field) {
            if (($field['type'] ?? 'text') !== 'select') {
                continue;
            }

            if (isset($field['enum'])) {
                $options[$name] = $field['enum'];

                continue;
            }

            if (! isset($field['option_source'])) {
                continue;
            }

            /** @var class-string<Model> $optionSource */
            $optionSource = $field['option_source'];
            $labelColumn = $field['option_label'] ?? 'name';

            $options[$name] = $optionSource::query()
                ->orderBy($labelColumn)
                ->pluck($labelColumn, 'id')
                ->all();
        }

        return $options;
    }

    /**
     * @return array<string, array{label:string,count:int,examples:array<int,string>,remaining_count:int}>
     */
    public static function linkedUsage(Model $record, string $lookup): array
    {
        $definition = self::get($lookup);
        $summary = [];
        $previewLimit = 5;

        foreach ($definition['usage_relations'] as $relation => $label) {
            if (! method_exists($record, $relation)) {
                continue;
            }

            /** @var Relation $relationQuery */
            $relationQuery = $record->{$relation}();
            $count = (int) $relationQuery->count();
            if ($count > 0) {
                $relatedRecords = $relationQuery->limit($previewLimit)->get();
                $summary[$relation] = [
                    'label' => $label,
                    'count' => $count,
                    'examples' => $relatedRecords
                        ->map(fn (Model $relatedRecord): string => self::relatedRecordLabel($relatedRecord))
                        ->values()
                        ->all(),
                    'remaining_count' => max(0, $count - $relatedRecords->count()),
                ];
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(string $lookup, ?Model $record = null): array
    {
        $definition = self::get($lookup);
        $rules = [];

        foreach ($definition['fields'] as $name => $field) {
            $fieldRules = [];
            $required = (bool) ($field['required'] ?? false);
            $type = $field['type'] ?? 'text';

            $fieldRules[] = $required ? 'required' : 'nullable';

            if ($type === 'textarea' || $type === 'text') {
                $fieldRules[] = 'string';
            }

            if ($type === 'number') {
                $fieldRules[] = 'integer';
            }

            if ($type === 'decimal') {
                $fieldRules[] = 'numeric';
            }

            if (isset($field['max']) && $type !== 'number') {
                $fieldRules[] = 'max:'.$field['max'];
            } elseif (in_array($type, ['text', 'select'], true) && ! isset($field['enum']) && ! isset($field['option_source'])) {
                $fieldRules[] = 'max:255';
            }

            if (in_array($type, ['number', 'decimal'], true) && isset($field['min'])) {
                $fieldRules[] = 'min:'.$field['min'];
            }

            if (in_array($type, ['number', 'decimal'], true) && isset($field['max'])) {
                $fieldRules[] = 'max:'.$field['max'];
            }

            if (isset($field['enum'])) {
                $fieldRules[] = Rule::in(array_keys($field['enum']));
            }

            if (isset($field['option_source'])) {
                /** @var class-string<Model> $optionSource */
                $optionSource = $field['option_source'];
                $fieldRules[] = Rule::exists((new $optionSource)->getTable(), 'id');
            }

            if (($field['unique'] ?? false) === true) {
                $uniqueRule = Rule::unique($definition['table'], $name);
                if ($record) {
                    $uniqueRule = $uniqueRule->ignore($record->getKey());
                }

                $fieldRules[] = $uniqueRule;
            }

            if (($field['nullable_unique'] ?? false) === true) {
                $uniqueRule = Rule::unique($definition['table'], $name);
                if ($record) {
                    $uniqueRule = $uniqueRule->ignore($record->getKey());
                }

                $fieldRules[] = $uniqueRule;
            }

            $rules[$name] = $fieldRules;
        }

        return $rules;
    }

    public static function displayValue(Model $record, string $lookup, string $fieldName): string
    {
        $definition = self::get($lookup);
        $field = $definition['fields'][$fieldName] ?? null;

        if (! $field) {
            return (string) data_get($record, $fieldName, '');
        }

        if (($field['type'] ?? null) === 'select' && isset($field['relation'])) {
            $related = data_get($record, $field['relation']);
            if ($related) {
                return (string) data_get($related, $field['option_label'] ?? 'name', '—');
            }

            return '—';
        }

        $value = data_get($record, $fieldName);

        return filled($value) ? (string) $value : '—';
    }

    public static function recordLabel(Model $record, string $lookup): string
    {
        $definition = self::get($lookup);
        $labelColumn = $definition['label_column'];
        $value = data_get($record, $labelColumn);

        return filled($value) ? (string) $value : '#'.$record->getKey();
    }

    /**
     * @return array<int, string>
     */
    public static function eagerLoadRelations(string $lookup): array
    {
        $definition = self::get($lookup);
        $relations = [];

        foreach ($definition['fields'] as $field) {
            if (($field['type'] ?? null) === 'select' && isset($field['relation'])) {
                $relations[] = $field['relation'];
            }
        }

        return array_values(array_unique($relations));
    }

    public static function preparePayload(array $validated, string $lookup): array
    {
        if ($lookup === 'studies' && ! array_key_exists('users_id', $validated)) {
            $validated['users_id'] = Auth::id();
        }

        return $validated;
    }

    private static function relatedRecordLabel(Model $record): string
    {
        $parts = [];

        $fullName = trim((string) data_get($record, 'first_name', '').' '.(string) data_get($record, 'last_name', ''));
        if ($fullName !== '') {
            $parts[] = $fullName;
        }

        foreach ([
            'name',
            'title',
            'species',
            'ref_key',
            'code',
            'sample_code',
            'alias_code',
            'field_label',
            'email',
            'name_common',
            'name_scientific',
            'genus',
            'family',
            'city',
        ] as $attribute) {
            $value = trim((string) data_get($record, $attribute, ''));
            if ($value !== '' && ! in_array($value, $parts, true)) {
                $parts[] = $value;
            }

            if (count($parts) >= 2) {
                break;
            }
        }

        if ($parts === []) {
            $parts[] = class_basename($record).' #'.$record->getKey();
        } else {
            $parts[] = '#'.$record->getKey();
        }

        return implode(' | ', $parts);
    }
}

<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\Animals;
use App\Models\AnimalSamples;
use App\Models\AnimalSpecies;
use App\Models\Countries;
use App\Models\Humans;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\Organizations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Services\AnimalSamplesService;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class AnimalSamplesImport extends PlainComponent
{
    use WithFileUploads;

    #[Validate('required|file|mimes:csv,txt,xlsx,xls|max:20480')]
    public $file;

    public string $status = 'idle';

    public array $globalIssues = [];

    public array $globalWarnings = [];

    /** @var array<int, array<string, string>> */
    public array $rowOverrides = [];

    /** @var list<string> */
    public array $headers = [];

    /** @var array<string,int> */
    public array $fieldToIndex = [];

    /**
     * - existing: rows refer to already registered animals via animal_code
     * - new: rows include animal fields and will create animals if missing
     */
    public string $animalMode = 'new';

    public int $page = 1;

    public int $perPage = 25;

    public ?string $cacheKey = null;

    public function updatedAnimalMode(): void
    {
        $this->animalMode = 'new';
    }

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->buildPreview();
    }

    public function downloadTemplate()
    {
        $headers = [
            'animal_species',
            'animal_species_scientific',
            'field_label',
            'sex',
            'age',
            'owner_type',
            'organization_name',
            'organization_country',
            'owner_first_name',
            'owner_last_name',
            'owner_country',
            'sample_type',
            'sample_type_category',
            'date_collected',
            'sampling_site',
            'sampling_site_country',
            'sampling_site_latitude',
            'sampling_site_longitude',
            'area',
            'latitude',
            'longitude',
            'location',
            'location_lab',
            'location_type',
            'location_room',
            'location_barcode',
            'collector_email',
            'collector_first_name',
            'collector_last_name',
            'immobilization_reason',
            'date_received',
            'storage_state',
        ];

        $exampleRow = [
            'Lion',
            'Panthera leo',
            'Lioness-07',
            'Female',
            'Adult',
            'organization',
            'Kruger Wildlife Vet Unit',
            'South Africa',
            '',
            '',
            'South Africa',
            'Swab',
            'host_derived',
            '2026-02-02',
            'Kruger Park - Site A',
            'South Africa',
            '-25.252000',
            '31.502000',
            'South Sector',
            '-25.252000',
            '31.502000',
            'Freezer A - Shelf 1',
            'Kruger Vet Lab',
            'Freezer',
            'Room 1',
            'LOC-001',
            'newcollector@example.org',
            'Carlo',
            'Rossi',
            'Darting',
            '2026-02-05',
            'Formalin',
        ];

        return response()->streamDownload(function () use ($headers, $exampleRow): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);
            fputcsv($output, $exampleRow);
            fclose($output);
        }, 'animal_samples_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function buildPreview(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import animal samples in this project (viewer accounts are read-only).';
            $this->status = 'error';

            return;
        }

        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            $this->globalIssues[] = 'Select a project before importing.';
            $this->status = 'error';

            return;
        }

        $reader = new DelimitedTableReader;

        try {
            $table = $reader->read($this->file);
        } catch (\Throwable $e) {
            $this->globalIssues[] = $e->getMessage();
            $this->status = 'error';

            return;
        }

        $this->headers = $table['headers'];
        $rows = $table['rows'];

        if (count($this->headers) === 0) {
            $this->globalIssues[] = 'No headers detected. Make sure the first row contains column names.';
            $this->status = 'error';

            return;
        }

        if (count($rows) === 0) {
            $this->globalIssues[] = 'No data rows detected.';
            $this->status = 'error';

            return;
        }

        $matcher = new ColumnMatcher;
        $match = $matcher->match($this->headers, $this->synonymsByField());
        $this->fieldToIndex = $match['fieldToIndex'];

        foreach ($this->requiredFields() as $required) {
            if (
                $required === 'owner_country' &&
                ! array_key_exists('owner_country', $this->fieldToIndex) &&
                (
                    array_key_exists('organization_country', $this->fieldToIndex) ||
                    $this->headerExists(['organization_country', 'organization country', 'org_country', 'owner_organization_country'])
                )
            ) {
                continue;
            }

            if (! array_key_exists($required, $this->fieldToIndex)) {
                $this->globalIssues[] = "Missing required column: {$required}";
            }
        }

        $this->autoFixSwappedSexAgeColumns($rows);

        $this->cacheKey = "imports:animal_samples:{$projectId}:".bin2hex(random_bytes(8));
        Cache::put($this->cacheKey, $rows, now()->addHour());

        $this->page = 1;
        $this->status = empty($this->globalIssues) ? 'preview' : 'error';
    }

    public function resetPreview(): void
    {
        if ($this->cacheKey) {
            Cache::forget($this->cacheKey);
        }

        $this->cacheKey = null;
        $this->headers = [];
        $this->fieldToIndex = [];
        $this->globalIssues = [];
        $this->globalWarnings = [];
        $this->rowOverrides = [];
        $this->status = 'idle';
        $this->page = 1;
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(10, min(200, (int) $this->perPage));
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $lastPage = $this->previewPage()['last_page'] ?? 1;
        $this->page = min(max(1, (int) $lastPage), $this->page + 1);
    }

    public function goToPage(int $page): void
    {
        $lastPage = $this->previewPage()['last_page'] ?? 1;
        $this->page = min(max(1, $page), max(1, (int) $lastPage));
    }

    public function applySuggestedValue(int $rowNumber, string $field, string $value): void
    {
        if ($rowNumber < 2) {
            return;
        }

        $this->rowOverrides[$rowNumber][$field] = $value;

        if ($field === 'sample_type') {
            $existingSampleType = $this->findSampleTypeByFlexibleName($value);
            if ($existingSampleType) {
                $this->rowOverrides[$rowNumber]['sample_type_category'] = (string) ($existingSampleType->category ?? '');
            }
        }

        if ($field === 'collector_email') {
            // Keep name fields user-controlled; selecting existing email should not force wrong names.
            $this->rowOverrides[$rowNumber]['collector_first_name'] = '';
            $this->rowOverrides[$rowNumber]['collector_last_name'] = '';
        }

        if ($field === 'owner_name') {
            $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($parts) >= 2) {
                $this->rowOverrides[$rowNumber]['owner_first_name'] = (string) array_shift($parts);
                $this->rowOverrides[$rowNumber]['owner_last_name'] = implode(' ', $parts);
            }
        }
    }

    public function import(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if ($this->status !== 'preview' || ! $this->cacheKey) {
            $this->globalIssues[] = 'Upload a file and fix issues before importing.';
            $this->status = 'error';

            return;
        }

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import animal samples in this project (viewer accounts are read-only).';
            $this->status = 'error';

            return;
        }

        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            $this->globalIssues[] = 'Select a project before importing.';
            $this->status = 'error';

            return;
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            $this->globalIssues[] = 'Import data expired. Please re-upload the file.';
            $this->status = 'error';

            return;
        }

        $service = new AnimalSamplesService;
        $project = Projects::findOrFail($projectId);

        $created = 0;
        $errors = 0;
        $rollbackSignal = '__animal_samples_bulk_import_rollback__';

        try {
            DB::transaction(function () use ($rows, $service, $project, &$created, &$errors, $rollbackSignal): void {
                $nextSerial = $this->nextAnimalSampleSerialForProject($project->id, $project->code);
                $nextAnimalSerial = $this->nextAnimalSerialForProject($project->id, $project->code);
                $nextHumanSerial = $this->nextHumanSerialForProject($project->id, $project->code);

                /** @var array<string, Animals> $createdAnimalsByKey */
                $createdAnimalsByKey = [];

                foreach ($rows as $i => $row) {
                    $rowNumber = $i + 2;
                    $resolved = $this->resolveRow(is_array($row) ? $row : [], $project->id, $rowNumber);

                    if (! empty($resolved['warnings'])) {
                        $this->globalWarnings[] = "Row {$rowNumber}: ".implode(' | ', $resolved['warnings']);
                    }

                    if (! empty($resolved['issues'])) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $resolved['issues']);

                        continue;
                    }

                    $fieldLabelKey = strtolower(trim($resolved['field_label']));
                    $existingAnimal = Animals::query()
                        ->where('projects_id', $project->id)
                        ->whereRaw('lower(field_label) = ?', [$fieldLabelKey])
                        ->first();

                    if ($existingAnimal) {
                        $animal = $existingAnimal;
                    } elseif (isset($createdAnimalsByKey[$fieldLabelKey])) {
                        $animal = $createdAnimalsByKey[$fieldLabelKey];
                    } else {
                        $speciesId = $this->resolveOrCreateAnimalSpeciesId(
                            $service,
                            $resolved['animal_species'],
                            $resolved['animal_species_scientific']
                        );

                        if ($speciesId === null) {
                            $errors++;
                            $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create animal_species: {$resolved['animal_species']}";

                            continue;
                        }

                        $owner = $this->resolveOrCreateOwner($service, $project->id, $project->code, $resolved, $nextHumanSerial);

                        if (! $owner) {
                            $errors++;
                            $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create owner (owner_type/owner_name/owner_country)";

                            continue;
                        }

                        $animalCode = $project->code.'-AN-'.$nextAnimalSerial;
                        $nextAnimalSerial++;

                        $animal = Animals::query()->create([
                            'code' => $animalCode,
                            'animal_species_id' => $speciesId,
                            'field_label' => $resolved['field_label'],
                            'sex' => $resolved['animal_sex'],
                            'age' => $resolved['animal_age'],
                            'owner_type' => $owner['type'],
                            'owner_id' => $owner['id'],
                            'projects_id' => $project->id,
                        ]);

                        $createdAnimalsByKey[$fieldLabelKey] = $animal;
                    }

                    $collector = People::query()
                        ->whereRaw('lower(email) = ?', [strtolower($resolved['collector_email'])])
                        ->first();

                    if (! $collector) {
                        if ($resolved['collector_first_name'] === '' || $resolved['collector_last_name'] === '') {
                            $errors++;
                            $this->globalIssues[] = "Row {$rowNumber}: collector_email not found, and collector_first_name/collector_last_name missing: {$resolved['collector_email']}";

                            continue;
                        }

                        $collector = People::query()->create([
                            'first_name' => $resolved['collector_first_name'],
                            'last_name' => $resolved['collector_last_name'],
                            'email' => $resolved['collector_email'],
                        ]);
                    }

                    $samplingSiteId = $this->resolveOrCreateSamplingSiteId(
                        $service,
                        $resolved['sampling_site'],
                        $resolved['sampling_site_country'],
                        $resolved['sampling_site_latitude'],
                        $resolved['sampling_site_longitude']
                    );
                    if ($samplingSiteId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create sampling_site: {$resolved['sampling_site']}";

                        continue;
                    }

                    $sampleTypeAttrs = [];
                    if ($resolved['sample_type_category'] !== '') {
                        $sampleTypeAttrs['category'] = $resolved['sample_type_category'];
                    }
                    $sampleTypeId = $service->check_or_create(SampleTypes::class, ['name' => $resolved['sample_type']], $sampleTypeAttrs);

                    $location = Locations::query()->where('name', $resolved['location'])->first();
                    if (! $location) {
                        $lab = Laboratories::query()->where('name', $resolved['location_lab'])->first();
                        if (! $lab) {
                            $labId = $service->check_or_create(Laboratories::class, ['name' => $resolved['location_lab']]);
                            $lab = Laboratories::find($labId);
                        }

                        if (! $lab) {
                            $errors++;
                            $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create location_lab: {$resolved['location_lab']}";

                            continue;
                        }

                        $locationId = $service->check_or_create(
                            Locations::class,
                            ['name' => $resolved['location']],
                            array_filter([
                                'type' => $resolved['location_type'] ?: null,
                                'laboratories_id' => $lab->id,
                                'room' => $resolved['location_room'] ?: null,
                                'barcode' => $resolved['location_barcode'] ?: null,
                            ], fn ($v) => $v !== null)
                        );
                        $location = Locations::find($locationId);
                    }

                    if (! $location) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create location: {$resolved['location']}";

                        continue;
                    }

                    $code = $project->code.'-AS-'.$nextSerial;
                    $nextSerial++;

                    AnimalSamples::query()->create([
                        'code' => $code,
                        'animals_id' => $animal->id,
                        'sample_types_id' => $sampleTypeId,
                        'date_collected' => $resolved['date_collected'],
                        'people_id' => $collector->id,
                        'sampling_sites_id' => $samplingSiteId,
                        'area' => $resolved['area'] ?: null,
                        'latitude' => $resolved['latitude'] !== '' ? (float) $resolved['latitude'] : null,
                        'longitude' => $resolved['longitude'] !== '' ? (float) $resolved['longitude'] : null,
                        'immobilization_reason' => $resolved['immobilization_reason'] ?: 'NA',
                        'locations_id' => $location->id,
                        'projects_id' => $project->id,
                        'storage_state' => $resolved['storage_state'] ?: null,
                        'date_received' => $resolved['date_received'] ?: null,
                    ]);

                    $created++;
                }

                if ($errors > 0) {
                    throw new \RuntimeException($rollbackSignal);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== $rollbackSignal) {
                throw $e;
            }
        }

        Cache::forget($this->cacheKey);
        $this->cacheKey = null;

        if ($errors > 0) {
            $this->status = 'error';
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Import failed',
                'text' => 'Bulk import failed. No data was registered.',
            ]);

            return;
        }

        $this->status = 'imported';
        $successMessage = "{$created} animal samples imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'animal_sample_created',
            'Bulk animal samples imported',
            $successMessage,
            '/samples/animals/list',
            $project->id
        );
        $this->dispatch('notification-created');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => $successMessage,
        ]);
    }

    public function openTemplateOptions(string $field): void
    {
        $projectId = $this->selectedProjectId();

        $result = $this->templateOptionsForField($field, $projectId);
        $values = $result['values'];
        $title = $result['title'];
        $total = $result['total'];
        $truncated = $result['truncated'];

        $itemsHtml = implode('', array_map(function (string $value): string {
            $escaped = e($value);

            return "<li class=\"py-1\"><span class=\"font-mono text-xs text-slate-800\">{$escaped}</span></li>";
        }, $values));

        $note = $truncated
            ? '<div class="mt-3 text-xs text-slate-500">Showing a subset of values for performance. Refine by typing the exact value in your CSV.</div>'
            : '';

        $html = <<<HTML
<div class="text-left">
  <div class="text-sm text-slate-700">{$total} value(s)</div>
  <div class="mt-3 max-h-96 overflow-auto rounded-lg border border-slate-200 bg-white px-4 py-3">
    <ul class="divide-y divide-slate-100">{$itemsHtml}</ul>
  </div>
  {$note}
</div>
HTML;

        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => $title,
            'html' => $html,
            'width' => '56rem',
            'showCloseButton' => true,
            'confirmButtonText' => 'Close',
        ]);
    }

    /**
     * @return array{
     *   animal_code: string,
     *   animal_species: string,
     *   field_label: string,
     *   animal_sex: string,
     *   animal_age: string,
     *   owner_type: string,
     *   owner_name: string,
     *   owner_country: string,
     *   organization_country: string,
     *   sample_type: string,
     *   sample_type_category: string,
     *   date_collected: string,
     *   sampling_site: string,
     *   sampling_site_country: string,
     *   sampling_site_latitude: string,
     *   sampling_site_longitude: string,
     *   location: string,
     *   location_lab: string,
     *   location_type: string,
     *   location_room: string,
     *   location_barcode: string,
     *   collector_email: string,
     *   area: string,
     *   latitude: string,
     *   longitude: string,
     *   immobilization_reason: string,
     *   storage_state: string,
     *   date_received: string,
     *   collector_first_name: string,
     *   collector_last_name: string,
     *   animal_resolution_note: string,
     *   warnings: list<string>,
     *   issues: list<string>
     * }
     */
    private function resolveRow(array $row, int $projectId, ?int $rowNumber = null): array
    {
        $get = function (string $field) use ($row, $rowNumber): string {
            if ($rowNumber !== null) {
                $override = $this->rowOverrides[$rowNumber][$field] ?? null;
                if ($override !== null && trim((string) $override) !== '') {
                    return $this->sanitizeCell((string) $override);
                }
            }

            $idx = $this->fieldToIndex[$field] ?? null;
            if ($idx === null) {
                return '';
            }

            return isset($row[$idx]) ? $this->sanitizeCell((string) $row[$idx]) : '';
        };

        $animalCode = $get('animal_code');
        $animalSpecies = $this->normalizeWordsTitleCase($get('animal_species'));
        $animalSpeciesScientific = $get('animal_species_scientific');
        $fieldLabel = $get('field_label');
        $animalSexRaw = $get('animal_sex');
        $animalAgeRaw = $get('animal_age');
        $animalSex = $this->normalizeAnimalSex($animalSexRaw);
        $animalAge = $this->normalizeAnimalAge($animalAgeRaw);
        $ownerType = strtolower(trim($get('owner_type')));
        $ownerName = $this->normalizeWordsTitleCase($get('owner_name'));
        $organizationNameRaw = $get('organization_name');
        if ($organizationNameRaw === '') {
            $organizationNameRaw = $this->directRowValueByHeaders(
                $row,
                ['organization_name', 'organization name', 'owner organization name', 'owner_organization_name']
            );
        }
        $organizationName = $this->normalizeWordsTitleCase($organizationNameRaw);

        $organizationCountryRaw = $get('organization_country');
        if ($organizationCountryRaw === '') {
            $organizationCountryRaw = $this->directRowValueByHeaders(
                $row,
                ['organization_country', 'organization country', 'org_country', 'owner_organization_country']
            );
        }
        $organizationCountry = $this->normalizeWordsTitleCase($organizationCountryRaw);
        $ownerFirstName = $this->normalizeWordsTitleCase($get('owner_first_name'));
        $ownerLastName = $this->normalizeWordsTitleCase($get('owner_last_name'));
        $ownerCountryRaw = $get('owner_country');
        if ($ownerCountryRaw === '') {
            $ownerCountryRaw = $this->directRowValueByHeaders(
                $row,
                ['owner_country', 'owner country', 'country_owner']
            );
        }
        $ownerCountry = $this->normalizeWordsTitleCase($ownerCountryRaw);
        $sampleType = $this->normalizeWordsTitleCase($get('sample_type'));
        $sampleTypeCategory = $this->normalizeSampleTypeCategory($get('sample_type_category'));
        $dateCollected = $get('date_collected');
        $samplingSite = $this->normalizeWordsTitleCase($get('sampling_site'));
        $samplingSiteCountry = $this->normalizeWordsTitleCase($get('sampling_site_country'));
        $samplingSiteLatitude = $get('sampling_site_latitude');
        $samplingSiteLongitude = $get('sampling_site_longitude');
        $location = $this->normalizeWordsTitleCase($get('location'));
        $locationLab = $this->normalizeWordsTitleCase($get('location_lab'));
        $collectorEmail = strtolower(trim($get('collector_email')));
        $collectorFirstName = $this->normalizeWordsTitleCase($get('collector_first_name'));
        $collectorLastName = $this->normalizeWordsTitleCase($get('collector_last_name'));
        if ($collectorEmail !== '' && mb_strtolower($collectorFirstName) === mb_strtolower($collectorEmail)) {
            $collectorFirstName = '';
        }
        if ($collectorEmail !== '' && mb_strtolower($collectorLastName) === mb_strtolower($collectorEmail)) {
            $collectorLastName = '';
        }

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        // Prevent fuzzy column mapping pollution (e.g. owner_type ending up in name fields).
        if (in_array($ownerType, ['individual', 'organization'], true)) {
            if (mb_strtolower($ownerName) === $ownerType) {
                $ownerName = '';
            }
            if (mb_strtolower($ownerFirstName) === $ownerType) {
                $ownerFirstName = '';
            }
            if (mb_strtolower($ownerLastName) === $ownerType) {
                $ownerLastName = '';
            }
            if (mb_strtolower($organizationName) === $ownerType) {
                $organizationName = '';
            }
            if (mb_strtolower($ownerCountry) === $ownerType) {
                $ownerCountry = '';
            }
        }

        if ($ownerType === 'organization' && $organizationName !== '') {
            $ownerName = $organizationName;
        }
        if ($ownerType === 'organization' && $organizationName === '' && $ownerName !== '') {
            $organizationName = $ownerName;
            $ownerName = $organizationName;
        }
        if ($ownerType === 'organization' && $ownerCountry === '' && $organizationCountry !== '') {
            $ownerCountry = $organizationCountry;
        }
        $organizationExistsExact = false;
        if ($ownerType === 'organization' && $organizationName !== '') {
            $existingOrganization = $this->findOrganizationByFlexibleName($organizationName);
            $organizationExistsExact = $existingOrganization !== null;
            if ($ownerCountry === '' && $existingOrganization && $existingOrganization->countries_id) {
                $existingCountryName = (string) data_get($existingOrganization->loadMissing('countries'), 'countries.name', '');
                if ($existingCountryName !== '') {
                    $ownerCountry = $this->normalizeWordsTitleCase($existingCountryName);
                }
            }
        }
        if ($ownerType === 'individual' && $ownerFirstName !== '' && $ownerLastName !== '') {
            $ownerName = trim($ownerFirstName.' '.$ownerLastName);
        }
        if ($ownerType === 'individual' && ($ownerFirstName === '' || $ownerLastName === '') && $ownerName !== '') {
            $parts = preg_split('/\s+/', $ownerName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($parts) >= 2) {
                $ownerFirstName = (string) array_shift($parts);
                $ownerLastName = implode(' ', $parts);
                $ownerName = trim($ownerFirstName.' '.$ownerLastName);
            }
        }
        $ownerIndividualExistsExact = false;
        if ($ownerType === 'individual' && $ownerFirstName !== '' && $ownerLastName !== '') {
            $existingHuman = $this->findHumanByFlexibleName($projectId, $ownerFirstName, $ownerLastName);
            $ownerIndividualExistsExact = $existingHuman !== null;
            if ($ownerCountry === '' && $existingHuman && $existingHuman->countries_id) {
                $existingCountryName = (string) data_get($existingHuman->loadMissing('countries'), 'countries.name', '');
                if ($existingCountryName !== '') {
                    $ownerCountry = $this->normalizeWordsTitleCase($existingCountryName);
                }
            }
        }

        if ($animalSpecies === '') {
            $issues[] = 'animal_species is required';
        }
        if ($fieldLabel === '') {
            $issues[] = 'field_label is required';
        }
        if ($animalSexRaw === '') {
            $issues[] = 'animal_sex is required';
        } elseif (! in_array($animalSex, ['Male', 'Female', 'NA'], true)) {
            $issues[] = "animal_sex must be Male, Female, or NA (got '{$animalSexRaw}')";
        }
        if ($animalAgeRaw === '') {
            $issues[] = 'animal_age is required';
        } elseif (! in_array($animalAge, ['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'], true)) {
            $issues[] = "animal_age must be Juvenile, Sub-adult, Adult, Old, or NA (got '{$animalAgeRaw}')";
        }
        if ($ownerType === '') {
            $issues[] = 'owner_type is required';
        } elseif (! in_array($ownerType, ['individual', 'organization'], true)) {
            $issues[] = 'owner_type must be individual or organization';
        }
        if ($ownerType === 'organization' && $organizationName === '') {
            $issues[] = 'organization_name is required when owner_type is organization';
        }
        if ($ownerType === 'individual' && $ownerFirstName === '') {
            $issues[] = 'owner_first_name is required when owner_type is individual';
        }
        if ($ownerType === 'individual' && $ownerLastName === '') {
            $issues[] = 'owner_last_name is required when owner_type is individual';
        }
        if ($ownerCountry === '') {
            if ($ownerType === 'organization') {
                if (! $organizationExistsExact && $organizationName !== '' && $organizationCountry === '') {
                    $issues[] = 'organization_country is required when organization_name is new';
                } else {
                    $issues[] = 'owner_country is required';
                }
            } elseif ($ownerType === 'individual') {
                if (! $ownerIndividualExistsExact) {
                    $issues[] = 'owner_country is required when owner is new (owner_type=individual)';
                } else {
                    $issues[] = 'owner_country is required (existing owner has no country set)';
                }
            } else {
                $issues[] = 'owner_country is required';
            }
        }
        if ($sampleType === '') {
            $issues[] = 'sample_type is required';
        }
        if ($dateCollected === '') {
            $issues[] = 'date_collected is required';
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateCollected)) {
            $issues[] = 'date_collected must be YYYY-MM-DD';
        }
        if ($samplingSite === '') {
            $issues[] = 'sampling_site is required';
        }
        if ($location === '') {
            $issues[] = 'location is required';
        }
        if ($collectorEmail === '') {
            $issues[] = 'collector_email is required';
        }

        $sampleTypeExistsExact = false;
        $existingSampleType = null;
        if ($sampleType !== '') {
            $existingSampleType = $this->findSampleTypeByFlexibleName($sampleType);
            $sampleTypeExistsExact = $existingSampleType !== null;
            if ($sampleTypeExistsExact) {
                $sampleType = (string) ($existingSampleType->name ?? $sampleType);
            }
        }

        // If category got auto-mapped from sample_type text, clear it for user selection.
        if (
            ! $sampleTypeExistsExact &&
            $sampleTypeCategory !== '' &&
            $this->normalizeLooseToken($sampleTypeCategory) === $this->normalizeLooseToken($sampleType)
        ) {
            $sampleTypeCategory = '';
        }

        // For existing sample types, show real existing category in preview.
        if ($sampleTypeExistsExact) {
            $sampleTypeCategory = (string) ($existingSampleType?->category ?? '');
        }

        if (! $sampleTypeExistsExact && $sampleTypeCategory !== '' && ! in_array($sampleTypeCategory, ['host_derived', 'non_host_derived'], true)) {
            $issues[] = 'sample_type_category must be host_derived or non_host_derived';
        }

        if ($sampleTypeCategory === '' && $sampleType !== '' && ! $sampleTypeExistsExact) {
            $issues[] = 'sample_type_category is required when sample_type is new';
        }
        if ($collectorEmail !== '') {
            $existsCollector = People::query()->whereRaw('lower(email) = ?', [strtolower($collectorEmail)])->exists();
            if (! $existsCollector) {
                $similarCollectorEmails = $this->similarEmailOptions($collectorEmail);
                if (! empty($similarCollectorEmails)) {
                    $existingJoined = implode(' and ', $similarCollectorEmails);
                    $warningText = "≈ {$existingJoined}";
                    $warnings[] = $warningText;
                    $fieldWarnings['collector_email'] = [
                        'text' => $warningText,
                        'suggested' => $similarCollectorEmails[0],
                        'options' => $similarCollectorEmails,
                    ];
                }

                if ($collectorFirstName === '' || $collectorLastName === '') {
                    $issues[] = "collector_email not found: {$collectorEmail} (provide collector_first_name and collector_last_name to create it)";
                }
            }
        }

        $collectorNameExistsExact = false;
        $collectorNameSuggestions = [];
        if ($collectorFirstName !== '' && $collectorLastName !== '') {
            $collectorNameExistsExact = People::query()
                ->whereRaw('lower(trim(first_name)) = ?', [strtolower(trim($collectorFirstName))])
                ->whereRaw('lower(trim(last_name)) = ?', [strtolower(trim($collectorLastName))])
                ->exists();
            if (! $collectorNameExistsExact) {
                $collectorFullName = trim($collectorFirstName.' '.$collectorLastName);
                $collectorNameSuggestions = $this->similarHumanNameOptions($collectorFullName);
                if (! empty($collectorNameSuggestions)) {
                    $fieldWarnings['collector_name'] = [
                        'text' => '≈ '.implode(' and ', $collectorNameSuggestions),
                        'suggested' => $collectorNameSuggestions[0],
                        'options' => $collectorNameSuggestions,
                    ];
                }
            }
        }

        $speciesExistsExact = false;
        $existingSpeciesByCommon = null;
        if ($animalSpecies !== '') {
            $existingSpeciesByCommon = $this->findAnimalSpeciesByCommonFlexible($animalSpecies);
            $speciesExistsExact = $existingSpeciesByCommon !== null;

            if (! $speciesExistsExact && $animalSpeciesScientific === '') {
                $issues[] = 'animal_species_scientific is required when animal_species is new';
            }

            $similarSpecies = $this->similarOptions(AnimalSpecies::query(), 'name_common', $animalSpecies);
            if (! empty($similarSpecies)) {
                $existingJoined = implode(' and ', $similarSpecies);
                $warningText = "≈ {$existingJoined}";
                $warnings[] = $warningText;
                $fieldWarnings['animal_species'] = [
                    'text' => $warningText,
                    'suggested' => $similarSpecies[0],
                    'options' => $similarSpecies,
                ];
            }
        }

        // If species already exists, scientific input is not needed in preview.
        if ($speciesExistsExact) {
            $animalSpeciesScientific = (string) ($existingSpeciesByCommon?->name_scientific ?? '');
        } elseif (
            $animalSpeciesScientific !== '' &&
            mb_strtolower($this->sanitizeCell($animalSpeciesScientific)) === mb_strtolower($this->sanitizeCell($animalSpecies))
        ) {
            // Prevent mirrored value from fuzzy column matching.
            $animalSpeciesScientific = '';
        }

        $animalSpeciesScientificConflict = false;
        if ($animalSpeciesScientific !== '') {
            $existingScientificExact = AnimalSpecies::query()
                ->whereRaw('lower(name_scientific) = ?', [strtolower($animalSpeciesScientific)])
                ->exists();
            if (! $speciesExistsExact && $existingScientificExact) {
                $issues[] = 'animal_species_scientific is already linked to an existing species; provide a unique scientific name for the new common name';
                $animalSpeciesScientificConflict = true;
            }

            $similarScientific = $this->similarOptions(AnimalSpecies::query(), 'name_scientific', $animalSpeciesScientific);
            if (! empty($similarScientific)) {
                $existingJoined = implode(' and ', $similarScientific);
                if ($speciesExistsExact === false) {
                    $issues[] = "animal_species_scientific: {$animalSpeciesScientific} ≈ {$existingJoined}";
                } else {
                    $warningText = "≈ {$existingJoined}";
                    $warnings[] = $warningText;
                    $fieldWarnings['animal_species_scientific'] = [
                        'text' => $warningText,
                        'suggested' => $similarScientific[0],
                        'options' => $similarScientific,
                    ];
                }
            }
        }

        if ($sampleType !== '') {
            $similarSampleType = $this->similarOptions(SampleTypes::query(), 'name', $sampleType);
            if (! empty($similarSampleType)) {
                $existingJoined = implode(' and ', $similarSampleType);
                $warningText = "≈ {$existingJoined}";
                $warnings[] = $warningText;
                $fieldWarnings['sample_type'] = [
                    'text' => $warningText,
                    'suggested' => $similarSampleType[0],
                    'options' => $similarSampleType,
                ];
            }
        }

        $samplingSiteExistsExact = false;
        $locationExistsExact = false;
        $locationLabExistsExact = false;
        if ($samplingSite !== '') {
            $similarSamplingSite = $this->similarOptions(SamplingSites::query(), 'name', $samplingSite);
            if (! empty($similarSamplingSite)) {
                $existingJoined = implode(' and ', $similarSamplingSite);
                $warningText = "≈ {$existingJoined}";
                $warnings[] = $warningText;
                $fieldWarnings['sampling_site'] = [
                    'text' => $warningText,
                    'suggested' => $similarSamplingSite[0],
                    'options' => $similarSamplingSite,
                ];
            }

            $samplingSiteExistsExact = SamplingSites::query()
                ->whereRaw('lower(name) = ?', [strtolower($samplingSite)])
                ->exists();
            if (! $samplingSiteExistsExact && $samplingSiteCountry === '') {
                $issues[] = 'sampling_site_country is required when sampling_site is new';
            }
        }

        if ($samplingSiteExistsExact) {
            $samplingSiteCountry = '';
        } elseif (
            $samplingSiteCountry !== '' &&
            mb_strtolower($this->sanitizeCell($samplingSiteCountry)) === mb_strtolower($this->sanitizeCell($samplingSite))
        ) {
            $samplingSiteCountry = '';
        }

        if ($location !== '') {
            $locationExistsExact = Locations::query()
                ->whereRaw('lower(name) = ?', [strtolower($location)])
                ->exists();

            $similarLocation = $this->similarOptions(Locations::query(), 'name', $location);
            if (! empty($similarLocation) && ! $locationExistsExact) {
                $existingJoined = implode(' and ', $similarLocation);
                $warningText = "≈ {$existingJoined}";
                $warnings[] = $warningText;
                $fieldWarnings['location'] = [
                    'text' => $warningText,
                    'suggested' => $similarLocation[0],
                    'options' => $similarLocation,
                ];
            }
        }

        if ($locationExistsExact) {
            $locationLab = '';
        } elseif (
            $locationLab !== '' &&
            mb_strtolower($this->sanitizeCell($locationLab)) === mb_strtolower($this->sanitizeCell($location))
        ) {
            $locationLab = '';
        }

        if ($location !== '' && ! $locationExistsExact && $locationLab === '') {
            $issues[] = 'location_lab is required to create a new location';
        }

        if ($locationLab !== '') {
            $locationLabExistsExact = Laboratories::query()
                ->whereRaw('lower(name) = ?', [strtolower($locationLab)])
                ->exists();

            $similarLocationLab = $this->similarOptions(Laboratories::query(), 'name', $locationLab);
            if (! empty($similarLocationLab) && ! $locationLabExistsExact) {
                $existingJoined = implode(' and ', $similarLocationLab);
                $warningText = "≈ {$existingJoined}";
                $warnings[] = $warningText;
                $fieldWarnings['location_lab'] = [
                    'text' => $warningText,
                    'suggested' => $similarLocationLab[0],
                    'options' => $similarLocationLab,
                ];
            }
        }

        $existingByFieldLabelCount = 0;
        $existingAnimalByFieldLabel = null;
        if ($fieldLabel !== '') {
            $existingAnimalByFieldLabel = Animals::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(field_label) = ?', [strtolower($fieldLabel)])
                ->first();
            $existingByFieldLabelCount = $existingAnimalByFieldLabel ? 1 : 0;
        }

        // Keep CSV-driven validation behavior: if sex/age are missing/invalid,
        // preview must continue showing error + correction dropdowns.

        $existingCollectorByEmail = null;
        if ($collectorEmail !== '') {
            $existingCollectorByEmail = People::query()
                ->whereRaw('lower(email) = ?', [strtolower($collectorEmail)])
                ->first();
        }

        if ($existingCollectorByEmail) {
            $collectorEmail = (string) ($existingCollectorByEmail->email ?? $collectorEmail);
            $collectorFirstName = '';
            $collectorLastName = '';
        }

        $animalResolutionNote = $existingByFieldLabelCount > 0
            ? 'Existing animal - samples linked'
            : 'New animal will be created';

        $animalResolutionBadge = $existingByFieldLabelCount > 0 ? 'reuse' : 'create';
        $ownerCountryExistsExact = $ownerCountry !== '' && Countries::query()->whereRaw('lower(name) = ?', [strtolower($ownerCountry)])->exists();
        if ($ownerCountry !== '' && ! $ownerCountryExistsExact) {
            $similarOwnerCountries = $this->similarOptions(Countries::query(), 'name', $ownerCountry);
            if (! empty($similarOwnerCountries)) {
                $fieldWarnings['owner_country'] = [
                    'text' => '≈ '.implode(' and ', $similarOwnerCountries),
                    'suggested' => $similarOwnerCountries[0],
                    'options' => $similarOwnerCountries,
                ];
            }
        }
        $samplingSiteCountryExistsExact = $samplingSiteCountry !== '' && Countries::query()->whereRaw('lower(name) = ?', [strtolower($samplingSiteCountry)])->exists();
        if ($locationLab !== '' && ! $locationLabExistsExact) {
            $locationLabExistsExact = Laboratories::query()->whereRaw('lower(name) = ?', [strtolower($locationLab)])->exists();
        }
        $collectorExistsExact = $existingCollectorByEmail !== null;
        $ownerNameExistsExact = false;
        $ownerOrganizationExistsExact = false;
        $ownerIndividualExistsExact = false;
        $ownerOrganizationSuggestions = [];
        $ownerIndividualSuggestions = [];
        if ($ownerType === 'organization' && $organizationName !== '') {
            $ownerOrganizationExistsExact = $this->findOrganizationByFlexibleName($organizationName) !== null;
            $ownerNameExistsExact = $ownerOrganizationExistsExact;
            if (! $ownerOrganizationExistsExact) {
                $ownerOrganizationSuggestions = $this->similarOptions(Organizations::query(), 'name', $organizationName);
            }
        }
        if ($ownerType === 'individual' && $ownerFirstName !== '' && $ownerLastName !== '') {
            $ownerIndividualValue = trim($ownerFirstName.' '.$ownerLastName);
            $ownerIndividualExistsExact = $this->findHumanByFlexibleName($projectId, $ownerFirstName, $ownerLastName) !== null;
            $ownerNameExistsExact = $ownerIndividualExistsExact;
            if (! $ownerIndividualExistsExact) {
                $ownerIndividualSuggestions = $this->similarHumanNameOptions($ownerIndividualValue);
            }
        }
        if (! empty($ownerOrganizationSuggestions)) {
            $fieldWarnings['organization_name'] = [
                'text' => '≈ '.implode(' and ', $ownerOrganizationSuggestions),
                'suggested' => $ownerOrganizationSuggestions[0],
                'options' => $ownerOrganizationSuggestions,
            ];
        }
        if (! empty($ownerIndividualSuggestions)) {
            $fieldWarnings['owner_individual_name'] = [
                'text' => '≈ '.implode(' and ', $ownerIndividualSuggestions),
                'suggested' => $ownerIndividualSuggestions[0],
                'options' => $ownerIndividualSuggestions,
            ];
        }
        $area = $this->sanitizeCell($get('area'));
        if ($samplingSite !== '' && mb_strtolower($area) === mb_strtolower($samplingSite)) {
            $area = '';
        }

        $animalSpeciesScientificExists = $animalSpeciesScientific !== '' && AnimalSpecies::query()->whereRaw('lower(name_scientific) = ?', [strtolower($animalSpeciesScientific)])->exists();
        $storageState = $this->normalizeWordsTitleCase($get('storage_state'));
        $immobilizationReason = $this->normalizeWordsTitleCase($get('immobilization_reason'));
        $storageStateExists = $storageState !== '' && AnimalSamples::query()->whereRaw('lower(storage_state) = ?', [strtolower($storageState)])->exists();
        $immobilizationReasonExists = $immobilizationReason !== '' && AnimalSamples::query()->whereRaw('lower(immobilization_reason) = ?', [strtolower($immobilizationReason)])->exists();

        return [
            'row_number' => $rowNumber ?? 0,
            'animal_code' => $animalCode,
            'animal_species' => $animalSpecies,
            'animal_species_scientific' => $animalSpeciesScientific,
            'field_label' => $fieldLabel,
            'animal_sex' => $animalSex,
            'animal_age' => $animalAge,
            'owner_type' => $ownerType,
            'owner_name' => $ownerName,
            'organization_name' => $organizationName,
            'organization_country' => $organizationCountry,
            'owner_first_name' => $ownerFirstName,
            'owner_last_name' => $ownerLastName,
            'owner_country' => $ownerCountry,
            'sample_type' => $sampleType,
            'sample_type_category' => $sampleTypeCategory,
            'date_collected' => $dateCollected,
            'sampling_site' => $samplingSite,
            'sampling_site_country' => $samplingSiteCountry,
            'sampling_site_latitude' => $samplingSiteLatitude,
            'sampling_site_longitude' => $samplingSiteLongitude,
            'location' => $location,
            'location_lab' => $locationLab,
            'location_type' => $get('location_type'),
            'location_room' => $get('location_room'),
            'location_barcode' => $get('location_barcode'),
            'collector_email' => $collectorEmail,
            'area' => $area,
            'latitude' => $get('latitude'),
            'longitude' => $get('longitude'),
            'immobilization_reason' => $immobilizationReason,
            'storage_state' => $storageState,
            'date_received' => $get('date_received'),
            'collector_first_name' => $collectorFirstName,
            'collector_last_name' => $collectorLastName,
            'collector_existing_first_name' => $existingCollectorByEmail ? (string) ($existingCollectorByEmail->first_name ?? '') : '',
            'collector_existing_last_name' => $existingCollectorByEmail ? (string) ($existingCollectorByEmail->last_name ?? '') : '',
            'animal_resolution_note' => $animalResolutionNote,
            'animal_resolution_badge' => $animalResolutionBadge,
            'field_label_exists' => $existingByFieldLabelCount > 0,
            'animal_species_exists' => $speciesExistsExact,
            'sample_type_exists' => $sampleTypeExistsExact,
            'sampling_site_exists' => $samplingSiteExistsExact,
            'owner_country_exists' => $ownerCountryExistsExact,
            'owner_name_exists' => $ownerNameExistsExact,
            'owner_organization_exists' => $ownerOrganizationExistsExact,
            'owner_individual_exists' => $ownerIndividualExistsExact,
            'sampling_site_country_exists' => $samplingSiteCountryExistsExact,
            'location_exists' => $locationExistsExact,
            'location_lab_exists' => $locationLabExistsExact,
            'collector_exists' => $collectorExistsExact,
            'collector_name_exists' => $collectorNameExistsExact,
            'animal_species_scientific_exists' => $animalSpeciesScientificExists,
            'animal_species_scientific_conflict' => $animalSpeciesScientificConflict,
            'storage_state_exists' => $storageStateExists,
            'immobilization_reason_exists' => $immobilizationReasonExists,
            'field_warnings' => $fieldWarnings,
            'animal_sex_invalid' => ! in_array($animalSex, ['Male', 'Female', 'NA'], true),
            'animal_age_invalid' => ! in_array($animalAge, ['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'], true),
            'warnings' => $warnings,
            'issues' => $issues,
        ];
    }

    private function normalizeAnimalSex(string $value): string
    {
        $v = strtolower($this->sanitizeCell($value));

        return match ($v) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            'na', 'n/a', 'n.a.', 'n.a', 'none', 'unknown' => 'NA',
            default => $value === '' ? '' : $value,
        };
    }

    private function normalizeAnimalAge(string $value): string
    {
        $v = strtolower($this->sanitizeCell($value));
        $compressed = preg_replace('/[^a-z]/', '', $v) ?? $v;

        return match ($compressed) {
            'juvenile' => 'Juvenile',
            'subadult' => 'Sub-adult',
            'adult' => 'Adult',
            'old' => 'Old',
            'na', 'none', 'unknown' => 'NA',
            default => $value === '' ? '' : $value,
        };
    }

    private function sanitizeCell(string $value): string
    {
        // Excel/CSV often contains BOM and non-breaking spaces.
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function normalizeWordsTitleCase(string $value): string
    {
        $value = $this->sanitizeCell($value);
        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function normalizeSampleTypeCategory(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        $normalized = str_replace(['-', ' '], '_', $value);
        $normalized = preg_replace('/_+/', '_', $normalized) ?? $normalized;
        $normalized = trim($normalized, '_');

        return match ($normalized) {
            'host_derived', 'hostderived' => 'host_derived',
            'non_host_derived', 'nonhost_derived', 'non_hostderived', 'nonhostderived' => 'non_host_derived',
            default => $normalized,
        };
    }

    private function normalizeLooseToken(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        $value = preg_replace('/[\s_-]+/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  list<string>  $aliases
     */
    private function headerExists(array $aliases): bool
    {
        $normalizedAliases = array_map(fn (string $alias): string => $this->normalizeHeaderToken($alias), $aliases);
        foreach ($this->headers as $header) {
            if (in_array($this->normalizeHeaderToken((string) $header), $normalizedAliases, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  list<string>  $aliases
     */
    private function directRowValueByHeaders(array $row, array $aliases): string
    {
        if (empty($row) || empty($this->headers)) {
            return '';
        }

        $normalizedAliases = array_map(fn (string $alias): string => $this->normalizeHeaderToken($alias), $aliases);
        foreach ($this->headers as $index => $header) {
            if (! in_array($this->normalizeHeaderToken((string) $header), $normalizedAliases, true)) {
                continue;
            }

            return isset($row[$index]) ? $this->sanitizeCell((string) $row[$index]) : '';
        }

        return '';
    }

    private function normalizeHeaderToken(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? $value;

        return trim($value);
    }

    private function findSampleTypeByFlexibleName(string $sampleType): ?SampleTypes
    {
        $sampleType = mb_strtolower($this->sanitizeCell($sampleType));
        if ($sampleType === '') {
            return null;
        }

        $sampleTypeCanonical = preg_replace('/[\s_-]+/', ' ', $sampleType) ?? $sampleType;
        $sampleTypeCanonical = trim($sampleTypeCanonical);

        $all = SampleTypes::query()->select('id', 'name', 'category')->get();
        foreach ($all as $candidate) {
            $candidateCanonical = mb_strtolower($this->sanitizeCell((string) $candidate->name));
            $candidateCanonical = preg_replace('/[\s_-]+/', ' ', $candidateCanonical) ?? $candidateCanonical;
            $candidateCanonical = trim($candidateCanonical);
            if ($candidateCanonical === $sampleTypeCanonical) {
                return $candidate;
            }
        }

        return null;
    }

    private function findAnimalSpeciesByCommonFlexible(string $nameCommon): ?AnimalSpecies
    {
        $nameCommon = mb_strtolower($this->sanitizeCell($nameCommon));
        if ($nameCommon === '') {
            return null;
        }

        $nameCommonCanonical = $this->canonicalEntityName($nameCommon);

        $all = AnimalSpecies::query()->select('id', 'name_common', 'name_scientific')->get();
        foreach ($all as $candidate) {
            $candidateCanonical = $this->canonicalEntityName((string) $candidate->name_common);
            if ($candidateCanonical === $nameCommonCanonical) {
                return $candidate;
            }
        }

        return null;
    }

    private function findOrganizationByFlexibleName(string $name): ?Organizations
    {
        $name = $this->sanitizeCell($name);
        if ($name === '') {
            return null;
        }

        $canonical = $this->canonicalEntityName($name);
        $all = Organizations::query()->select('id', 'name', 'countries_id')->get();
        foreach ($all as $candidate) {
            $candidateCanonical = $this->canonicalEntityName((string) $candidate->name);
            if ($candidateCanonical === $canonical) {
                return $candidate;
            }
        }

        return null;
    }

    private function findHumanByFlexibleName(int $projectId, string $firstName, string $lastName): ?Humans
    {
        $firstName = $this->sanitizeCell($firstName);
        $lastName = $this->sanitizeCell($lastName);
        if ($firstName === '' || $lastName === '') {
            return null;
        }

        $exact = Humans::query()
            ->where('projects_id', $projectId)
            ->whereRaw('lower(trim(first_name)) = ?', [strtolower(trim($firstName))])
            ->whereRaw('lower(trim(last_name)) = ?', [strtolower(trim($lastName))])
            ->first();
        if ($exact) {
            return $exact;
        }

        $exactGlobal = Humans::query()
            ->whereRaw('lower(trim(first_name)) = ?', [strtolower(trim($firstName))])
            ->whereRaw('lower(trim(last_name)) = ?', [strtolower(trim($lastName))])
            ->first();
        if ($exactGlobal) {
            return $exactGlobal;
        }

        $targetCanonical = $this->canonicalEntityName(trim($firstName.' '.$lastName));
        $candidates = Humans::query()->select('id', 'first_name', 'last_name')->get();
        foreach ($candidates as $candidate) {
            $candidateCanonical = $this->canonicalEntityName(trim(((string) $candidate->first_name).' '.((string) $candidate->last_name)));
            if ($candidateCanonical === $targetCanonical) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function similarEmailOptions(string $email, int $limit = 2): array
    {
        $email = mb_strtolower($this->sanitizeCell($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return [];
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return [];
        }

        $candidates = $this->projectPeopleEmailsQuery($this->selectedProjectId())
            ->whereRaw('lower(email) like ?', ['%@'.$domain])
            ->pluck('email')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $scored = [];
        foreach ($candidates as $candidateEmail) {
            $candidateLower = mb_strtolower($this->sanitizeCell($candidateEmail));
            if ($candidateLower === $email || ! str_contains($candidateLower, '@')) {
                continue;
            }

            [$candidateLocal, $candidateDomain] = array_pad(explode('@', $candidateLower, 2), 2, '');
            if ($candidateDomain !== $domain || $candidateLocal === '') {
                continue;
            }

            similar_text($local, $candidateLocal, $pct);
            $score = $pct;
            if (str_starts_with($candidateLocal, $local) || str_starts_with($local, $candidateLocal)) {
                $score = max($score, 90.0);
            }

            if ($score >= 72.0) {
                $scored[] = ['email' => $candidateEmail, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        $seen = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['email'];
            $key = mb_strtolower($this->sanitizeCell($candidate));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $candidate;
            }
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function similarHumanNameOptions(string $fullName, int $limit = 2): array
    {
        $fullName = $this->sanitizeCell($fullName);
        if ($fullName === '') {
            return [];
        }

        $candidates = Humans::query()
            ->select('first_name', 'last_name')
            ->get()
            ->map(fn ($h) => trim(((string) $h->first_name).' '.((string) $h->last_name)))
            ->filter()
            ->values()
            ->all();

        return $this->similarFromCandidates($fullName, $candidates, $limit);
    }

    /**
     * @param  list<string>  $candidates
     * @return list<string>
     */
    private function similarFromCandidates(string $value, array $candidates, int $limit = 2): array
    {
        $valueCanonical = $this->canonicalEntityName($value);
        $scored = [];
        foreach ($candidates as $candidate) {
            if (mb_strtolower($this->sanitizeCell($candidate)) === mb_strtolower($this->sanitizeCell($value))) {
                continue;
            }
            $candidateCanonical = $this->canonicalEntityName($candidate);
            similar_text($valueCanonical, $candidateCanonical, $pct);
            $score = max($pct, $this->tokenOverlapScore($valueCanonical, $candidateCanonical), $this->tokenPrefixScore($valueCanonical, $candidateCanonical));
            if ($score >= 72.0) {
                $scored[] = ['candidate' => $candidate, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $result = [];
        $seen = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['candidate'];
            $key = mb_strtolower($this->sanitizeCell($candidate));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $candidate;
            }
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * Some CSVs end up mapping "sex" and "age" swapped due to fuzzy header matching.
     * Detect this by inspecting the first row and swap indices when clearly reversed.
     *
     * @param  list<array<int, mixed>>  $rows
     */
    private function autoFixSwappedSexAgeColumns(array $rows): void
    {
        $sexIdx = $this->fieldToIndex['animal_sex'] ?? null;
        $ageIdx = $this->fieldToIndex['animal_age'] ?? null;

        if ($sexIdx === null || $ageIdx === null) {
            return;
        }

        if (! isset($rows[0]) || ! is_array($rows[0])) {
            return;
        }

        $firstRow = $rows[0];
        $sexRaw = isset($firstRow[$sexIdx]) ? $this->sanitizeCell((string) $firstRow[$sexIdx]) : '';
        $ageRaw = isset($firstRow[$ageIdx]) ? $this->sanitizeCell((string) $firstRow[$ageIdx]) : '';

        $sexNorm = $this->normalizeAnimalSex($sexRaw);
        $ageNorm = $this->normalizeAnimalAge($ageRaw);

        $sexLooksLikeSex = in_array($sexNorm, ['Male', 'Female', 'NA'], true);
        $ageLooksLikeAge = in_array($ageNorm, ['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'], true);

        if ($sexLooksLikeSex && $ageLooksLikeAge) {
            return;
        }

        $sexLooksLikeAge = in_array($sexNorm, ['Juvenile', 'Sub-adult', 'Adult', 'Old'], true);
        $ageLooksLikeSex = in_array($ageNorm, ['Male', 'Female', 'NA'], true);

        if ($sexLooksLikeAge && $ageLooksLikeSex) {
            $this->fieldToIndex['animal_sex'] = $ageIdx;
            $this->fieldToIndex['animal_age'] = $sexIdx;
            $this->globalWarnings[] = 'Detected swapped sex/age columns in the CSV and auto-corrected the mapping.';
        }
    }

    private function resolveOrCreateAnimalSpeciesId(
        AnimalSamplesService $service,
        string $nameCommon,
        string $nameScientific
    ): ?int {
        $nameCommon = trim($nameCommon);
        $nameScientific = trim($nameScientific);
        if ($nameCommon === '') {
            return null;
        }

        $existing = AnimalSpecies::query()
            ->whereRaw('lower(name_common) = ?', [strtolower($nameCommon)])
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        if ($nameScientific === '') {
            return null;
        }

        $scientific = $nameScientific;
        $suffix = 1;
        while (AnimalSpecies::query()->whereRaw('lower(name_scientific) = ?', [strtolower($scientific)])->exists()) {
            $suffix++;
            $scientific = "{$nameScientific}_{$suffix}";
        }

        $species = AnimalSpecies::query()->create([
            'name_common' => $nameCommon,
            'name_scientific' => $scientific,
        ]);

        return (int) $species->id;
    }

    /**
     * @param array{
     *   owner_type: string,
     *   owner_name: string,
     *   organization_name: string,
     *   owner_first_name: string,
     *   owner_last_name: string,
     *   owner_country: string,
     *   organization_country: string
     * } $resolved
     * @return array{type: class-string, id: int}|null
     */
    private function resolveOrCreateOwner(AnimalSamplesService $service, int $projectId, string $projectCode, array $resolved, int &$nextHumanSerial): ?array
    {
        $type = strtolower(trim($resolved['owner_type'] ?? ''));
        $name = trim((string) ($resolved['owner_name'] ?? ''));
        $organizationName = trim((string) ($resolved['organization_name'] ?? ''));
        $ownerFirstName = trim((string) ($resolved['owner_first_name'] ?? ''));
        $ownerLastName = trim((string) ($resolved['owner_last_name'] ?? ''));
        $country = trim((string) ($resolved['owner_country'] ?? ''));

        if ($type === '') {
            return null;
        }

        $countryId = 0;
        if ($country !== '') {
            $countryId = (int) $service->check_or_create(Countries::class, ['name' => $country]);
        }

        if ($type === 'organization') {
            $orgName = $organizationName !== '' ? $organizationName : $name;
            if ($orgName === '') {
                return null;
            }
            $org = $this->findOrganizationByFlexibleName($orgName);
            if (! $org) {
                $org = Organizations::query()
                    ->whereRaw('lower(name) = ?', [strtolower($orgName)])
                    ->first();
            }

            if (! $org) {
                if ($countryId <= 0) {
                    return null;
                }
                $orgId = (int) $service->check_or_create(Organizations::class, ['name' => $orgName], ['countries_id' => $countryId]);
                $org = Organizations::find($orgId);
            }

            if (! $org) {
                return null;
            }

            return ['type' => Organizations::class, 'id' => (int) $org->id];
        }

        if ($type === 'individual') {
            if ($ownerFirstName === '' || $ownerLastName === '') {
                return null;
            }
            $firstName = $ownerFirstName;
            $lastName = $ownerLastName;

            $human = Humans::query()
                ->whereRaw('lower(first_name) = ?', [strtolower($firstName)])
                ->whereRaw('lower(last_name) = ?', [strtolower($lastName)])
                ->first();

            if (! $human) {
                if ($countryId <= 0) {
                    $existingHuman = $this->findHumanByFlexibleName($projectId, $firstName, $lastName);
                    if ($existingHuman && $existingHuman->countries_id) {
                        $countryId = (int) $existingHuman->countries_id;
                    } else {
                        return null;
                    }
                }
                $humanCode = $projectCode.'-HU-'.$nextHumanSerial;
                $nextHumanSerial++;

                $human = Humans::query()->create([
                    'projects_id' => $projectId,
                    'code' => $humanCode,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'countries_id' => $countryId,
                ]);
            } elseif ($countryId <= 0) {
                if ($human->countries_id) {
                    $countryId = (int) $human->countries_id;
                } else {
                    return null;
                }
            }

            return ['type' => Humans::class, 'id' => (int) $human->id];
        }

        return null;
    }

    private function resolveOrCreateSamplingSiteId(
        AnimalSamplesService $service,
        string $name,
        string $countryName = '',
        string $latitude = '',
        string $longitude = ''
    ): ?int {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $existing = SamplingSites::query()
            ->whereRaw('lower(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $countryName = trim($countryName);
        if ($countryName === '') {
            return null;
        }

        $countryId = (int) $service->check_or_create(Countries::class, ['name' => $countryName]);

        $site = SamplingSites::query()->create([
            'name' => $name,
            'countries_id' => $countryId,
            'latitude' => $latitude !== '' ? (float) $latitude : null,
            'longitude' => $longitude !== '' ? (float) $longitude : null,
        ]);

        return (int) $site->id;
    }

    /**
     * @param  Builder  $query
     */
    private function bestSimilarMatch($query, string $column, string $value, int $threshold = 72): ?string
    {
        $value = trim($value);
        if (mb_strlen($value) < 4) {
            return null;
        }

        $candidates = $query
            ->limit(300)
            ->pluck($column)
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $best = null;
        $bestScore = 0.0;
        $valueCanonical = $this->canonicalEntityName($value);
        foreach ($candidates as $candidate) {
            $candidateCanonical = $this->canonicalEntityName($candidate);

            if ($candidateCanonical === $valueCanonical) {
                if (mb_strtolower($candidate) !== mb_strtolower($value)) {
                    return $candidate;
                }

                continue;
            }

            similar_text($valueCanonical, $candidateCanonical, $pct);
            $tokenScore = $this->tokenOverlapScore($valueCanonical, $candidateCanonical);
            $prefixScore = $this->tokenPrefixScore($valueCanonical, $candidateCanonical);
            $score = max($pct, $tokenScore, $prefixScore);
            if (str_contains(' '.$candidateCanonical.' ', ' '.$valueCanonical.' ') || str_contains(' '.$valueCanonical.' ', ' '.$candidateCanonical.' ')) {
                $score = max($score, 88.0);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        if ($best !== null && $bestScore >= $threshold) {
            if (mb_strtolower($best) === mb_strtolower($value)) {
                return null;
            }

            return $best;
        }

        return null;
    }

    /**
     * @param  Builder  $query
     * @return list<string>
     */
    private function similarOptions($query, string $column, string $value, int $threshold = 72, int $limit = 2): array
    {
        $value = trim($value);
        if (mb_strlen($value) < 3) {
            return [];
        }

        $candidates = $query
            ->limit(400)
            ->pluck($column)
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $valueCanonical = $this->canonicalEntityName($value);
        $scored = [];

        foreach ($candidates as $candidate) {
            if (mb_strtolower($this->sanitizeCell($candidate)) === mb_strtolower($this->sanitizeCell($value))) {
                continue;
            }

            $candidateCanonical = $this->canonicalEntityName($candidate);
            similar_text($valueCanonical, $candidateCanonical, $pct);
            $tokenScore = $this->tokenOverlapScore($valueCanonical, $candidateCanonical);
            $prefixScore = $this->tokenPrefixScore($valueCanonical, $candidateCanonical);
            $score = max($pct, $tokenScore, $prefixScore);
            if (str_contains(' '.$candidateCanonical.' ', ' '.$valueCanonical.' ') || str_contains(' '.$valueCanonical.' ', ' '.$candidateCanonical.' ')) {
                $score = max($score, 88.0);
            }

            if ($score >= $threshold) {
                $scored[] = ['candidate' => $candidate, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        $resultKeys = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['candidate'];
            $candidateKey = mb_strtolower($this->sanitizeCell($candidate));
            if (str_contains($candidateKey, '@')) {
                $candidateKey = strtolower($candidateKey);
            }
            if (! isset($resultKeys[$candidateKey])) {
                $result[] = $candidate;
                $resultKeys[$candidateKey] = true;
            }
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    private function canonicalEntityName(string $value): string
    {
        $normalized = mb_strtolower($this->sanitizeCell($value));
        $normalized = str_replace(['.', ',', ';', ':', '-', '_', '/', '\\', '(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $mapped = [];
        foreach ($tokens as $token) {
            $mapped[] = match ($token) {
                'nat', 'natl', 'national' => 'np',
                'park', 'parks' => 'np',
                'npark' => 'np',
                default => $token,
            };
        }

        $mapped = array_values(array_unique($mapped));
        sort($mapped);

        return implode(' ', $mapped);
    }

    private function tokenOverlapScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = array_intersect($left, $right);
        $union = array_unique(array_merge($left, $right));
        if (count($union) === 0) {
            return 0.0;
        }

        return (count($intersection) / count($union)) * 100;
    }

    private function tokenPrefixScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $shorter = count($left) <= count($right) ? $left : $right;
        $longer = count($left) <= count($right) ? $right : $left;
        $matched = 0;

        foreach ($shorter as $shortToken) {
            foreach ($longer as $longToken) {
                if ($shortToken === $longToken || str_starts_with($longToken, $shortToken) || str_starts_with($shortToken, $longToken)) {
                    $matched++;
                    break;
                }
            }
        }

        return (count($shorter) > 0) ? (($matched / count($shorter)) * 100) : 0.0;
    }

    private function organizationNameExists(string $ownerName): bool
    {
        $ownerName = $this->sanitizeCell($ownerName);
        if ($ownerName === '') {
            return false;
        }

        if (Organizations::query()->whereRaw('lower(name) = ?', [strtolower($ownerName)])->exists()) {
            return true;
        }

        $ownerCanonical = $this->canonicalEntityName($ownerName);
        $candidates = Organizations::query()->pluck('name')->filter()->map(fn ($v) => (string) $v)->all();
        foreach ($candidates as $candidate) {
            $candidateCanonical = $this->canonicalEntityName($candidate);
            if ($candidateCanonical === $ownerCanonical || $this->tokenPrefixScore($ownerCanonical, $candidateCanonical) >= 95.0) {
                return true;
            }
        }

        return false;
    }

    private function individualNameExists(string $ownerName): bool
    {
        $ownerName = $this->sanitizeCell($ownerName);
        if ($ownerName === '') {
            return false;
        }

        $parts = preg_split('/\s+/', $ownerName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) >= 2) {
            $firstName = array_shift($parts);
            $lastName = implode(' ', $parts);
            if (
                Humans::query()
                    ->whereRaw('lower(first_name) = ?', [strtolower((string) $firstName)])
                    ->whereRaw('lower(last_name) = ?', [strtolower((string) $lastName)])
                    ->exists()
            ) {
                return true;
            }
        }

        $ownerCanonical = $this->canonicalEntityName($ownerName);
        $candidateHumans = Humans::query()->select('first_name', 'last_name')->get();
        foreach ($candidateHumans as $human) {
            $fullName = trim(((string) $human->first_name).' '.((string) $human->last_name));
            if ($fullName === '') {
                continue;
            }
            $candidateCanonical = $this->canonicalEntityName($fullName);
            if ($candidateCanonical === $ownerCanonical || $this->tokenPrefixScore($ownerCanonical, $candidateCanonical) >= 95.0) {
                return true;
            }
        }

        return false;
    }

    private function nextAnimalSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = Animals::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-AN-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-AN-(\d+)$/', (string) $code, $m);

            return isset($m[1]) ? (int) $m[1] : null;
        })->filter()->sort()->values();

        // Use the highest existing serial + 1 so that sequential bulk inserts
        // never collide with serials that already exist after a gap in the
        // sequence (gap-filling is unsafe once the loop increments past the gap).
        return $used->isEmpty() ? 1 : ((int) $used->max() + 1);
    }

    private function nextHumanSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = Humans::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-HU-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-HU-(\d+)$/', (string) $code, $m);

            return isset($m[1]) ? (int) $m[1] : null;
        })->filter()->sort()->values();

        // Use the highest existing serial + 1 so that sequential bulk inserts
        // never collide with serials that already exist after a gap in the
        // sequence (gap-filling is unsafe once the loop increments past the gap).
        return $used->isEmpty() ? 1 : ((int) $used->max() + 1);
    }

    private function nextAnimalSampleSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = AnimalSamples::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-AS-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-AS-(\d+)$/', (string) $code, $m);

            return isset($m[1]) ? (int) $m[1] : null;
        })->filter()->sort()->values();

        // Use the highest existing serial + 1 so that sequential bulk inserts
        // never collide with serials that already exist after a gap in the
        // sequence (gap-filling is unsafe once the loop increments past the gap).
        return $used->isEmpty() ? 1 : ((int) $used->max() + 1);
    }

    /**
     * @return list<string>
     */
    private function requiredFields(): array
    {
        return [
            'animal_species',
            'field_label',
            'animal_sex',
            'animal_age',
            'owner_type',
            'owner_country',
            'sample_type',
            'date_collected',
            'sampling_site',
            'location',
            'collector_email',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'animal_code' => ['animal_code', 'animal code', 'animal_id_code', 'animal_id', 'animal id', 'animal id code'],
            'animal_species' => ['animal_species', 'animal species', 'species', 'animal_species_name', 'animal species common', 'species_common'],
            'animal_species_scientific' => ['animal_species_scientific', 'animal species scientific', 'species_scientific', 'scientific_name', 'name_scientific'],
            'field_label' => ['field_label', 'field label', 'label', 'animal_field_label'],
            'animal_sex' => ['animal_sex', 'sex', 'animal sex'],
            'animal_age' => ['animal_age', 'age', 'animal age'],
            'owner_type' => ['owner_type', 'owner type'],
            'organization_name' => ['organization_name', 'organization name', 'owner organization name', 'owner_organization_name'],
            'organization_country' => ['organization_country', 'organization country', 'org_country', 'owner_organization_country'],
            'owner_name' => ['owner_name', 'owner name', 'owner_full_name', 'owner organization', 'owner person', 'owner'],
            'owner_first_name' => ['owner_first_name', 'owner first name', 'owner_first', 'owner firstname'],
            'owner_last_name' => ['owner_last_name', 'owner last name', 'owner_last', 'owner lastname', 'owner surname'],
            'owner_country' => ['owner_country', 'owner country', 'country_owner', 'organization_country', 'organization country', 'org_country', 'owner_organization_country'],
            'sample_type' => ['sample_type', 'sample type', 'type', 'sampletype'],
            'sample_type_category' => ['sample_type_category', 'sample category', 'category'],
            'date_collected' => ['date_collected', 'date', 'collection_date', 'collected_date'],
            'sampling_site' => ['sampling_site', 'sampling site', 'site'],
            'sampling_site_country' => ['sampling_site_country', 'sampling site country', 'site_country', 'country', 'sampling_country'],
            'sampling_site_latitude' => ['sampling_site_latitude', 'site_latitude', 'site_lat'],
            'sampling_site_longitude' => ['sampling_site_longitude', 'site_longitude', 'site_lon', 'site_lng'],
            'area' => ['area', 'sampling_area'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'lon', 'lng'],
            'immobilization_reason' => ['immobilization_reason', 'reason_immobilization', 'immobilization'],
            'location' => ['location', 'storage_location', 'location_name'],
            'location_lab' => ['location_lab', 'lab', 'laboratory', 'laboratory_name'],
            'location_type' => ['location_type', 'storage_type'],
            'location_room' => ['location_room', 'room'],
            'location_barcode' => ['location_barcode', 'barcode'],
            'storage_state' => ['storage_state', 'preservant', 'preservative'],
            'date_received' => ['date_received', 'received_date'],
            'collector_email' => ['collector_email', 'collector', 'collected_by_email', 'people_email'],
            'collector_first_name' => ['collector_first_name', 'collector first name', 'collector_first', 'collector firstname', 'collected_by_first_name'],
            'collector_last_name' => ['collector_last_name', 'collector last name', 'collector_last', 'collector lastname', 'collected_by_last_name'],
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int, from: int, to: int, current_page: int, last_page: int, start_page: int, end_page: int}
     */
    public function previewPage(): array
    {
        if (! $this->cacheKey) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1];
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1];
        }

        $total = count($rows);
        $perPage = max(10, min(200, (int) $this->perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $this->page), $lastPage);
        $this->page = $page;
        $offset = ($page - 1) * $perPage;

        $slice = array_slice($rows, $offset, $perPage);
        $mapped = [];
        $projectId = $this->selectedProjectId() ?? 0;
        foreach ($slice as $row) {
            $rowNumber = $offset + count($mapped) + 2;
            $mapped[] = $this->resolveRow(is_array($row) ? $row : [], (int) $projectId, $rowNumber);
        }

        return [
            'rows' => $mapped,
            'total' => $total,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($total, $offset + $perPage),
            'current_page' => $page,
            'last_page' => $lastPage,
            'start_page' => max(1, $page - 2),
            'end_page' => min($lastPage, $page + 2),
        ];
    }

    public function render()
    {
        return view('livewire.imports.animal-samples-import', [
            'template' => $this->templateDefinition(),
        ]);
    }

    /**
     * @return array{columns:list<array{
     *   header:string,
     *   field:string,
     *   required:'required'|'optional'|'conditional',
     *   description:string,
     *   format:string,
     *   accepted:list<string>,
     *   aliases:list<string>,
     *   create_policy:string,
     *   create_notes:string,
     *   example:string,
     *   options:list<string>,
     *   options_total:int
     * }>}
     */
    private function templateDefinition(): array
    {
        $projectId = $this->selectedProjectId();

        $options = Cache::remember(
            'imports:animal_samples:template_options:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'species_common' => $this->optionPreview(AnimalSpecies::query()->orderBy('name_common'), 'name_common'),
                    'species_scientific' => $this->optionPreview(AnimalSpecies::query()->orderBy('name_scientific'), 'name_scientific'),
                    'countries' => $this->optionPreview(Countries::query()->orderBy('name'), 'name'),
                    'sample_types' => $this->optionPreview(SampleTypes::query()->orderBy('name'), 'name'),
                    'sampling_sites' => $this->optionPreview(SamplingSites::query()->orderBy('name'), 'name'),
                    'laboratories' => $this->optionPreview(Laboratories::query()->orderBy('name'), 'name'),
                    'locations' => $this->optionPreview(Locations::query()->orderBy('name'), 'name'),
                    'organizations' => $this->optionPreview(Organizations::query()->orderBy('name'), 'name'),
                    'collector_emails' => $this->optionPreview(
                        $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                        'email'
                    ),
                    'humans' => $this->optionPreviewHumanNames($projectId),
                    'immobilization_reason' => $this->optionPreviewDistinct(AnimalSamples::query(), 'immobilization_reason'),
                    'storage_state' => $this->optionPreviewDistinct(AnimalSamples::query(), 'storage_state'),
                ];
            }
        );

        $aliases = $this->synonymsByField();

        $columns = [
            [
                'header' => 'animal_species',
                'field' => 'animal_species',
                'required' => 'required',
                'description' => 'Common species name for the animal. Used to link or create the species record.',
                'format' => 'Text (e.g. Lion).',
                'accepted' => [],
                'aliases' => $aliases['animal_species'] ?? [],
                'create_policy' => 'Creates new AnimalSpecies if missing.',
                'create_notes' => 'If new, you must also provide animal_species_scientific.',
                'example' => 'Lion',
                'options' => $options['species_common']['values'],
                'options_total' => $options['species_common']['total'],
            ],
            [
                'header' => 'animal_species_scientific',
                'field' => 'animal_species_scientific',
                'required' => 'conditional',
                'description' => 'Scientific name for the species.',
                'format' => 'Text (Genus species), e.g. Panthera leo.',
                'accepted' => [],
                'aliases' => $aliases['animal_species_scientific'] ?? [],
                'create_policy' => 'Required only when animal_species is new.',
                'create_notes' => 'If species already exists, this is ignored (we use the stored scientific name).',
                'example' => 'Panthera leo',
                'options' => $options['species_scientific']['values'],
                'options_total' => $options['species_scientific']['total'],
            ],
            [
                'header' => 'field_label',
                'field' => 'field_label',
                'required' => 'required',
                'description' => 'Your animal identifier (field label). Used to link to an existing animal in the selected project or create a new one.',
                'format' => 'Text (unique within project recommended).',
                'accepted' => [],
                'aliases' => $aliases['field_label'] ?? [],
                'create_policy' => 'Creates new Animals if missing.',
                'create_notes' => 'If an animal with the same field_label exists in this project, the sample links to it.',
                'example' => 'Lioness-07',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sex',
                'field' => 'animal_sex',
                'required' => 'required',
                'description' => 'Sex of the animal.',
                'format' => 'One of the accepted values.',
                'accepted' => ['Male', 'Female', 'NA'],
                'aliases' => $aliases['animal_sex'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Female',
                'options' => ['Male', 'Female', 'NA'],
                'options_total' => 3,
            ],
            [
                'header' => 'age',
                'field' => 'animal_age',
                'required' => 'required',
                'description' => 'Age class of the animal.',
                'format' => 'One of the accepted values.',
                'accepted' => ['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'],
                'aliases' => $aliases['animal_age'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Adult',
                'options' => ['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'],
                'options_total' => 5,
            ],
            [
                'header' => 'owner_type',
                'field' => 'owner_type',
                'required' => 'required',
                'description' => 'Whether the owner is an individual (Human) or an organization.',
                'format' => 'individual OR organization (lowercase).',
                'accepted' => ['individual', 'organization'],
                'aliases' => $aliases['owner_type'] ?? [],
                'create_policy' => 'Links to existing records if exact match; otherwise creates new.',
                'create_notes' => 'If individual: owner_first_name + owner_last_name required. If organization: organization_name required.',
                'example' => 'organization',
                'options' => ['individual', 'organization'],
                'options_total' => 2,
            ],
            [
                'header' => 'organization_name',
                'field' => 'organization_name',
                'required' => 'conditional',
                'description' => 'Organization owner name (only when owner_type=organization).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['organization_name'] ?? [],
                'create_policy' => 'Creates new Organization if missing.',
                'create_notes' => 'If the organization is new, you must provide a country via owner_country or organization_country.',
                'example' => 'Kruger Wildlife Vet Unit',
                'options' => $options['organizations']['values'],
                'options_total' => $options['organizations']['total'],
            ],
            [
                'header' => 'organization_country',
                'field' => 'organization_country',
                'required' => 'conditional',
                'description' => 'Country of the organization (needed only when owner_type=organization and the organization is new).',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['organization_country'] ?? [],
                'create_policy' => 'Creates country if missing.',
                'create_notes' => 'If owner_country is empty, organization_country is used as the owner country.',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'owner_first_name',
                'field' => 'owner_first_name',
                'required' => 'conditional',
                'description' => 'Owner first name (only when owner_type=individual).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['owner_first_name'] ?? [],
                'create_policy' => 'Creates a new Human if missing (global match attempted first).',
                'create_notes' => 'Must be paired with owner_last_name.',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'owner_last_name',
                'field' => 'owner_last_name',
                'required' => 'conditional',
                'description' => 'Owner last name (only when owner_type=individual).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['owner_last_name'] ?? [],
                'create_policy' => 'Creates a new Human if missing (global match attempted first).',
                'create_notes' => 'Must be paired with owner_first_name.',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'owner_country',
                'field' => 'owner_country',
                'required' => 'conditional',
                'description' => 'Country of the owner.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['owner_country'] ?? [],
                'create_policy' => 'Creates country if missing.',
                'create_notes' => 'Required when owner_type=individual and the owner is new. For organizations, the country can be inferred from an existing organization or provided via organization_country.',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'sample_type',
                'field' => 'sample_type',
                'required' => 'required',
                'description' => 'Sample type name.',
                'format' => 'Text (e.g. Swab, Blood).',
                'accepted' => [],
                'aliases' => $aliases['sample_type'] ?? [],
                'create_policy' => 'Creates new SampleType if missing.',
                'create_notes' => 'If new, sample_type_category becomes required.',
                'example' => 'Swab',
                'options' => $options['sample_types']['values'],
                'options_total' => $options['sample_types']['total'],
            ],
            [
                'header' => 'sample_type_category',
                'field' => 'sample_type_category',
                'required' => 'conditional',
                'description' => 'Category for a new sample_type.',
                'format' => 'host_derived OR non_host_derived.',
                'accepted' => ['host_derived', 'non_host_derived'],
                'aliases' => $aliases['sample_type_category'] ?? [],
                'create_policy' => 'Required only when sample_type is new.',
                'create_notes' => '',
                'example' => 'host_derived',
                'options' => ['host_derived', 'non_host_derived'],
                'options_total' => 2,
            ],
            [
                'header' => 'date_collected',
                'field' => 'date_collected',
                'required' => 'required',
                'description' => 'Collection date.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_collected'] ?? [],
                'create_policy' => 'Must match format.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sampling_site',
                'field' => 'sampling_site',
                'required' => 'required',
                'description' => 'Sampling site name.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['sampling_site'] ?? [],
                'create_policy' => 'Creates new SamplingSite if missing.',
                'create_notes' => 'If new, sampling_site_country becomes required.',
                'example' => 'Kruger Park - Site A',
                'options' => $options['sampling_sites']['values'],
                'options_total' => $options['sampling_sites']['total'],
            ],
            [
                'header' => 'sampling_site_country',
                'field' => 'sampling_site_country',
                'required' => 'conditional',
                'description' => 'Country for a new sampling_site.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site_country'] ?? [],
                'create_policy' => 'Required only when sampling_site is new.',
                'create_notes' => '',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'sampling_site_latitude',
                'field' => 'sampling_site_latitude',
                'required' => 'optional',
                'description' => 'Latitude for a new sampling_site.',
                'format' => 'Decimal degrees (e.g. -25.252).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site_latitude'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '-25.252000',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sampling_site_longitude',
                'field' => 'sampling_site_longitude',
                'required' => 'optional',
                'description' => 'Longitude for a new sampling_site.',
                'format' => 'Decimal degrees (e.g. 31.502).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site_longitude'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '31.502000',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'latitude',
                'field' => 'latitude',
                'required' => 'optional',
                'description' => 'Sample latitude (if available).',
                'format' => 'Decimal degrees.',
                'accepted' => [],
                'aliases' => $aliases['latitude'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '-25.252000',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'longitude',
                'field' => 'longitude',
                'required' => 'optional',
                'description' => 'Sample longitude (if available).',
                'format' => 'Decimal degrees.',
                'accepted' => [],
                'aliases' => $aliases['longitude'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '31.502000',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'location',
                'field' => 'location',
                'required' => 'required',
                'description' => 'Storage location name.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['location'] ?? [],
                'create_policy' => 'Creates new Location if missing.',
                'create_notes' => 'If new, location_lab becomes required.',
                'example' => 'Freezer A - Shelf 1',
                'options' => $options['locations']['values'],
                'options_total' => $options['locations']['total'],
            ],
            [
                'header' => 'location_lab',
                'field' => 'location_lab',
                'required' => 'conditional',
                'description' => 'Laboratory name for creating a new location.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['location_lab'] ?? [],
                'create_policy' => 'Required only when location is new.',
                'create_notes' => 'Creates new Laboratory if missing.',
                'example' => 'Kruger Vet Lab',
                'options' => $options['laboratories']['values'],
                'options_total' => $options['laboratories']['total'],
            ],
            [
                'header' => 'collector_email',
                'field' => 'collector_email',
                'required' => 'required',
                'description' => 'Collector email (person record).',
                'format' => 'Email.',
                'accepted' => [],
                'aliases' => $aliases['collector_email'] ?? [],
                'create_policy' => 'Creates new People record if missing.',
                'create_notes' => 'If new, collector_first_name + collector_last_name become required.',
                'example' => 'newcollector@example.org',
                'options' => $options['collector_emails']['values'],
                'options_total' => $options['collector_emails']['total'],
            ],
            [
                'header' => 'collector_first_name',
                'field' => 'collector_first_name',
                'required' => 'conditional',
                'description' => 'Collector first name for creating a new collector_email.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_first_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Carlo',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'collector_last_name',
                'field' => 'collector_last_name',
                'required' => 'conditional',
                'description' => 'Collector last name for creating a new collector_email.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_last_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Rossi',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'immobilization_reason',
                'field' => 'immobilization_reason',
                'required' => 'optional',
                'description' => 'Immobilization reason. Use NA if unknown.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['immobilization_reason'] ?? [],
                'create_policy' => 'Free text (defaults to NA if empty).',
                'create_notes' => '',
                'example' => 'Darting',
                'options' => $options['immobilization_reason']['values'],
                'options_total' => $options['immobilization_reason']['total'],
            ],
            [
                'header' => 'date_received',
                'field' => 'date_received',
                'required' => 'optional',
                'description' => 'Date received by the lab/storage.',
                'format' => 'YYYY-MM-DD (recommended).',
                'accepted' => [],
                'aliases' => $aliases['date_received'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'storage_state',
                'field' => 'storage_state',
                'required' => 'optional',
                'description' => 'Storage state / preservative.',
                'format' => 'Text (e.g. Formalin).',
                'accepted' => [],
                'aliases' => $aliases['storage_state'] ?? [],
                'create_policy' => 'Free text.',
                'create_notes' => '',
                'example' => 'Formalin',
                'options' => $options['storage_state']['values'],
                'options_total' => $options['storage_state']['total'],
            ],
        ];

        foreach ($columns as &$col) {
            $aliasList = array_values(array_unique(array_filter(array_map('strval', $col['aliases'] ?? []))));
            if (! in_array($col['header'], $aliasList, true)) {
                array_unshift($aliasList, $col['header']);
            }
            $col['aliases'] = $aliasList;
        }
        unset($col);

        return [
            'columns' => $columns,
        ];
    }

    /**
     * @param  string|Expression  $column
     * @return array{values:list<string>, total:int}
     */
    private function optionPreview(Builder $query, $column, int $limit = 10): array
    {
        $total = (int) (clone $query)->count();

        $values = (clone $query)
            ->limit($limit)
            ->pluck($column)
            ->filter()
            ->map(fn ($v): string => trim((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'values' => $values,
            'total' => $total,
        ];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreviewDistinct(Builder $query, string $column, int $limit = 10): array
    {
        $values = (clone $query)
            ->select($column)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->map(fn ($v): string => trim((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->values()
            ->all();

        $total = (int) (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->count($column);

        return [
            'values' => $values,
            'total' => $total,
        ];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreviewHumanNames(?int $projectId, int $limit = 10): array
    {
        $query = Humans::query()
            ->when($projectId, fn (Builder $q) => $q->where('projects_id', $projectId))
            ->select(['first_name', 'last_name']);

        $total = (int) (clone $query)->count();

        $values = (clone $query)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn (Humans $human): string => trim((string) $human->first_name.' '.(string) $human->last_name))
            ->filter()
            ->values()
            ->all();

        return [
            'values' => $values,
            'total' => $total,
        ];
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);
        $limit = 250;

        $make = function (string $title, Builder $query, string $column) use ($limit): array {
            $total = (int) (clone $query)->count();
            $values = (clone $query)
                ->limit($limit)
                ->pluck($column)
                ->filter()
                ->map(fn ($v) => (string) $v)
                ->values()
                ->all();

            return [
                'title' => $title,
                'values' => $values,
                'total' => $total,
                'truncated' => $total > count($values),
            ];
        };

        return match ($field) {
            'animal_species' => $make('Existing values: animal_species', AnimalSpecies::query()->orderBy('name_common'), 'name_common'),
            'animal_species_scientific' => $make('Existing values: animal_species_scientific', AnimalSpecies::query()->orderBy('name_scientific'), 'name_scientific'),
            'organization_name' => $make('Existing values: organization_name', Organizations::query()->orderBy('name'), 'name'),
            'owner_country',
            'organization_country',
            'sampling_site_country', => $make('Existing values: countries', Countries::query()->orderBy('name'), 'name'),
            'sample_type' => $make('Existing values: sample_type', SampleTypes::query()->orderBy('name'), 'name'),
            'sampling_site' => $make('Existing values: sampling_site', SamplingSites::query()->orderBy('name'), 'name'),
            'location' => $make('Existing values: location', Locations::query()->orderBy('name'), 'name'),
            'location_lab' => $make('Existing values: location_lab', Laboratories::query()->orderBy('name'), 'name'),
            'collector_email' => $make(
                'Existing values: collector_email',
                $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                'email'
            ),
            'immobilization_reason' => $make(
                'Existing values: immobilization_reason',
                AnimalSamples::query()->select('immobilization_reason')->whereNotNull('immobilization_reason')->where('immobilization_reason', '!=', '')->distinct()->orderBy('immobilization_reason'),
                'immobilization_reason'
            ),
            'storage_state' => $make(
                'Existing values: storage_state',
                AnimalSamples::query()->select('storage_state')->whereNotNull('storage_state')->where('storage_state', '!=', '')->distinct()->orderBy('storage_state'),
                'storage_state'
            ),
            'owner_first_name',
            'owner_last_name',
            'owner_name', => [
                'title' => 'Existing values: owners',
                'values' => $this->optionPreviewHumanNames($projectId, $limit)['values'],
                'total' => $this->optionPreviewHumanNames($projectId, $limit)['total'],
                'truncated' => $this->optionPreviewHumanNames($projectId, $limit)['total'] > count($this->optionPreviewHumanNames($projectId, $limit)['values']),
            ],
            default => [
                'title' => "Existing values: {$field}",
                'values' => [],
                'total' => 0,
                'truncated' => false,
            ],
        };
    }

    private function projectPeopleEmailsQuery(?int $projectId): Builder
    {
        if (! $projectId) {
            return People::query()->whereRaw('1 = 0');
        }

        return People::query()
            ->whereIn('id', function ($query) use ($projectId): void {
                $query->from('projects_people')
                    ->select('people_id')
                    ->where('projects_id', $projectId);
            })
            ->whereNotNull('email')
            ->where('email', '!=', '');
    }
}

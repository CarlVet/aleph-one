<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\Countries;
use App\Models\Humans;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SamplingSites;
use App\Services\HumanSamplesService;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class HumanSamplesImport extends PlainComponent
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

    /** @var array<string, int> */
    public array $fieldToIndex = [];

    public int $page = 1;

    public int $perPage = 25;

    public ?string $cacheKey = null;

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->buildPreview();
    }

    public function downloadTemplate()
    {
        $template = $this->templateDefinition();
        $columns = (array) ($template['columns'] ?? []);

        $headers = array_values(array_map(
            static fn (array $col): string => (string) ($col['header'] ?? ''),
            $columns
        ));

        $exampleRow = array_values(array_map(
            static fn (array $col): string => (string) ($col['example'] ?? ''),
            $columns
        ));

        return response()->streamDownload(function () use ($headers, $exampleRow): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);
            fputcsv($output, $exampleRow);
            fclose($output);
        }, 'human_samples_import_template.csv', [
            'Content-Type' => 'text/csv',
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

    public function buildPreview(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import human samples in this project (viewer accounts are read-only).';
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
            if (! array_key_exists($required, $this->fieldToIndex)) {
                $this->globalIssues[] = "Missing required column: {$required}";
            }
        }

        $this->cacheKey = "imports:human_samples:{$projectId}:".bin2hex(random_bytes(8));
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
            $this->globalIssues[] = 'You do not have permission to import human samples in this project (viewer accounts are read-only).';
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

        $service = new HumanSamplesService;
        $project = Projects::findOrFail($projectId);

        $created = 0;
        $errors = 0;
        $rollbackSignal = '__human_samples_bulk_import_rollback__';

        try {
            DB::transaction(function () use ($rows, $service, $project, &$created, &$errors, $rollbackSignal): void {
                $nextSerial = $this->nextHumanSampleSerialForProject($project->id, $project->code);

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

                    $countryId = (int) $service->check_or_create(Countries::class, ['name' => $resolved['human_country']]);

                    $human = $this->findExistingHumanByImportData($project->id, $resolved, $countryId);
                    if (! $human) {
                        $humanCode = $project->code.'-HU-'.$this->nextHumanSerialForProject($project->id, $project->code);
                        $human = Humans::query()->create([
                            'projects_id' => $project->id,
                            'code' => $humanCode,
                            'field_label' => $resolved['field_label'] !== '' ? $resolved['field_label'] : null,
                            'first_name' => $resolved['human_first_name'],
                            'last_name' => $resolved['human_last_name'],
                            'sex' => $resolved['human_sex'] ?: null,
                            'date_of_birth' => $resolved['human_date_of_birth'] ?: null,
                            'ethnicity' => $resolved['human_ethnicity'] ?: null,
                            'occupation' => $resolved['human_occupation'] ?: null,
                            'street' => $resolved['human_street'] ?: null,
                            'city' => $resolved['human_city'] ?: null,
                            'province' => $resolved['human_province'] ?: null,
                            'postal_code' => $resolved['human_postal_code'] ?: null,
                            'countries_id' => $countryId,
                            'preferred_contact_method' => $resolved['human_preferred_contact_method'] ?: null,
                            'phone' => $resolved['human_phone'] ?: null,
                            'alternate_phone' => $resolved['human_alternate_phone'] ?: null,
                            'email' => $resolved['human_email'] ?: null,
                            'alternate_email' => $resolved['human_alternate_email'] ?: null,
                            'national_id' => $resolved['human_national_id'] ?: null,
                            'marital_status' => $resolved['human_marital_status'] ?: null,
                            'insurance_provider' => $resolved['human_insurance_provider'] ?: null,
                            'insurance_id' => $resolved['human_insurance_id'] ?: null,
                        ]);
                    } elseif (! filled($human->field_label) && $resolved['field_label'] !== '') {
                        $human->update([
                            'field_label' => $resolved['field_label'],
                        ]);
                    }

                    $sampleTypeAttrs = [];
                    if ($resolved['sample_type_category'] !== '') {
                        $sampleTypeAttrs['category'] = $resolved['sample_type_category'];
                    }
                    $sampleTypeId = $service->check_or_create(SampleTypes::class, ['name' => $resolved['sample_type']], $sampleTypeAttrs);

                    $collector = People::query()
                        ->whereRaw('lower(email) = ?', [strtolower($resolved['collector_email'])])
                        ->first();

                    if (! $collector) {
                        $collector = People::query()->create([
                            'first_name' => $resolved['collector_first_name'],
                            'last_name' => $resolved['collector_last_name'],
                            'email' => $resolved['collector_email'],
                        ]);
                    }

                    $samplingSiteId = $this->resolveOrCreateSamplingSiteId($service, $resolved['sampling_site'], $resolved['sampling_site_country']);
                    if ($samplingSiteId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create sampling_site: {$resolved['sampling_site']}";

                        continue;
                    }

                    $locationId = $this->resolveOrCreateLocationId($service, $resolved['location'], $resolved['location_lab']);
                    if ($locationId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create location: {$resolved['location']}";

                        continue;
                    }

                    $code = $project->code.'-HS-'.$nextSerial;
                    $nextSerial++;

                    HumanSamples::query()->create([
                        'code' => $code,
                        'humans_id' => $human->id,
                        'sample_types_id' => $sampleTypeId,
                        'date_collected' => $resolved['date_collected'],
                        'people_id' => $collector->id,
                        'sampling_sites_id' => $samplingSiteId,
                        'area' => $resolved['area'] ?: null,
                        'latitude' => $resolved['latitude'] !== '' ? (float) $resolved['latitude'] : null,
                        'longitude' => $resolved['longitude'] !== '' ? (float) $resolved['longitude'] : null,
                        'sample_purpose' => $resolved['sampling_purpose'],
                        'locations_id' => $locationId,
                        'storage_state' => $resolved['storage_state'] ?: null,
                        'processed' => false,
                        'projects_id' => $project->id,
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
        $successMessage = "{$created} human samples imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'human_sample_created',
            'Bulk human samples imported',
            $successMessage,
            '/samples/humans/list',
            $project->id
        );
        $this->dispatch('notification-created');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => $successMessage,
        ]);
    }

    /**
     * @return array{
     *   row_number: int,
     *   human_first_name: string,
     *   human_last_name: string,
     *   human_sex: string,
     *   human_date_of_birth: string,
     *   human_ethnicity: string,
     *   human_occupation: string,
     *   human_marital_status: string,
     *   human_country: string,
     *   human_city: string,
     *   human_province: string,
     *   human_street: string,
     *   human_postal_code: string,
     *   human_preferred_contact_method: string,
     *   human_phone: string,
     *   human_alternate_phone: string,
     *   human_email: string,
     *   human_alternate_email: string,
     *   human_national_id: string,
     *   human_insurance_provider: string,
     *   human_insurance_id: string,
     *   sample_type: string,
     *   sample_type_category: string,
     *   field_label: string,
     *   date_collected: string,
     *   sampling_purpose: string,
     *   storage_state: string,
     *   sampling_site: string,
     *   sampling_site_country: string,
     *   area: string,
     *   latitude: string,
     *   longitude: string,
     *   location: string,
     *   location_lab: string,
     *   collector_email: string,
     *   collector_first_name: string,
     *   collector_last_name: string,
     *   human_exists: bool,
     *   human_country_exists: bool,
     *   sample_type_exists: bool,
     *   sampling_site_exists: bool,
     *   sampling_site_country_exists: bool,
     *   location_exists: bool,
     *   location_lab_exists: bool,
     *   collector_exists: bool,
     *   collector_first_name_exists: bool,
     *   collector_last_name_exists: bool,
     *   sampling_purpose_invalid: bool,
     *   storage_state_invalid: bool,
     *   field_warnings: array<string, array{text:string,suggested:string,options:list<string>}>,
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

        $humanFirstName = $this->normalizeWordsTitleCase($get('human_first_name'));
        $humanLastName = $this->normalizeWordsTitleCase($get('human_last_name'));
        $humanSex = $this->normalizeHumanSex($get('human_sex'));
        $humanDateOfBirth = $get('human_date_of_birth');
        $humanEthnicity = $this->normalizeWordsTitleCase($get('human_ethnicity'));
        $humanOccupation = $this->normalizeWordsTitleCase($get('human_occupation'));
        $humanMaritalStatus = $this->normalizeWordsTitleCase($get('human_marital_status'));
        $humanCountry = $this->normalizeWordsTitleCase($get('human_country'));
        $humanCity = $this->normalizeWordsTitleCase($get('human_city'));
        $humanProvince = $this->normalizeWordsTitleCase($get('human_province'));
        $humanStreet = $this->sanitizeCell($get('human_street'));
        $humanPostalCode = $this->sanitizeCell($get('human_postal_code'));
        $humanPreferredContactMethod = $this->normalizePreferredContactMethod($get('human_preferred_contact_method'));
        $humanPhone = $this->sanitizeCell($get('human_phone'));
        $humanAlternatePhone = $this->sanitizeCell($get('human_alternate_phone'));
        $humanEmail = strtolower(trim($get('human_email')));
        $humanAlternateEmail = strtolower(trim($get('human_alternate_email')));
        $humanNationalId = $this->sanitizeCell($get('human_national_id'));
        $humanInsuranceProvider = $this->sanitizeCell($get('human_insurance_provider'));
        $humanInsuranceId = $this->sanitizeCell($get('human_insurance_id'));
        $sampleType = $this->normalizeWordsTitleCase($get('sample_type'));
        $fieldLabel = $this->sanitizeCell($get('field_label'));
        $sampleTypeCategory = $this->normalizeSampleTypeCategory($get('sample_type_category'));
        $dateCollected = $get('date_collected');
        $samplingPurpose = $this->normalizeSamplingPurpose($get('sampling_purpose'));
        $storageState = $this->normalizeStorageState($get('storage_state'));
        $samplingSite = $this->normalizeWordsTitleCase($get('sampling_site'));
        $samplingSiteCountry = $this->normalizeWordsTitleCase($get('sampling_site_country'));
        $area = $this->sanitizeCell($get('area'));
        $latitude = $this->sanitizeCell($get('latitude'));
        $longitude = $this->sanitizeCell($get('longitude'));
        $location = $this->normalizeWordsTitleCase($get('location'));
        $locationLab = $this->normalizeWordsTitleCase($get('location_lab'));
        $collectorEmail = strtolower(trim($get('collector_email')));
        $collectorFirstName = $this->normalizeWordsTitleCase($get('collector_first_name'));
        $collectorLastName = $this->normalizeWordsTitleCase($get('collector_last_name'));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        if ($humanFirstName === '') {
            $issues[] = 'human_first_name is required';
        }
        if ($humanLastName === '') {
            $issues[] = 'human_last_name is required';
        }
        if ($humanCountry === '') {
            $issues[] = 'human_country is required';
        }
        if ($humanDateOfBirth !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $humanDateOfBirth)) {
            $issues[] = 'human_date_of_birth must be YYYY-MM-DD when provided';
        }
        if ($humanSex !== '' && ! in_array($humanSex, ['Male', 'Female'], true)) {
            $issues[] = 'human_sex must be Male or Female when provided';
        }
        if ($humanPreferredContactMethod !== '' && ! in_array($humanPreferredContactMethod, ['phone', 'email', 'sms'], true)) {
            $issues[] = 'human_preferred_contact_method must be phone, email, or sms when provided';
        }
        if ($sampleType === '') {
            $issues[] = 'sample_type is required';
        }
        if ($dateCollected === '') {
            $issues[] = 'date_collected is required';
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateCollected)) {
            $issues[] = 'date_collected must be YYYY-MM-DD';
        }
        if ($samplingPurpose === '') {
            $issues[] = 'sampling_purpose is required';
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

        if ($latitude !== '' && ! is_numeric($latitude)) {
            $issues[] = 'latitude must be numeric';
        }
        if ($longitude !== '' && ! is_numeric($longitude)) {
            $issues[] = 'longitude must be numeric';
        }

        $humanExists = false;
        $humanCountryExists = false;
        if ($humanCountry !== '') {
            $humanCountryExists = Countries::query()
                ->whereRaw('lower(name) = ?', [strtolower($humanCountry)])
                ->exists();
            if (! $humanCountryExists) {
                $similarCountries = $this->similarOptions(Countries::query(), 'name', $humanCountry);
                if (! empty($similarCountries)) {
                    $fieldWarnings['human_country'] = [
                        'text' => '≈ '.implode(' and ', $similarCountries),
                        'suggested' => $similarCountries[0],
                        'options' => $similarCountries,
                    ];
                    $warnings[] = $fieldWarnings['human_country']['text'];
                }
            }
        }

        if ($humanFirstName !== '' && $humanLastName !== '' && $humanCountry !== '') {
            $countryId = Countries::query()
                ->whereRaw('lower(name) = ?', [strtolower($humanCountry)])
                ->value('id');

            if ($countryId) {
                $humanExists = $this->findExistingHumanByImportData($projectId, [
                    'human_first_name' => $humanFirstName,
                    'human_last_name' => $humanLastName,
                    'human_date_of_birth' => $humanDateOfBirth,
                    'human_email' => $humanEmail,
                    'human_national_id' => $humanNationalId,
                ], (int) $countryId) !== null;
            }

            $humanName = "{$humanFirstName} {$humanLastName}";
            $similarHumanNames = $this->similarOptions(
                Humans::query()
                    ->where('projects_id', $projectId)
                    ->selectRaw("CONCAT(first_name, ' ', last_name) as full_name"),
                'full_name',
                $humanName
            );
            if (! empty($similarHumanNames) && ! $humanExists) {
                $fieldWarnings['human_name'] = [
                    'text' => '≈ '.implode(' and ', $similarHumanNames),
                    'suggested' => $similarHumanNames[0],
                    'options' => $similarHumanNames,
                ];
                $warnings[] = $fieldWarnings['human_name']['text'];
            }
        }

        if ($humanFirstName !== '') {
            $similarHumanFirstNames = $this->similarOptions(
                Humans::query()->where('projects_id', $projectId),
                'first_name',
                $humanFirstName
            );
            if (! empty($similarHumanFirstNames)) {
                $fieldWarnings['human_first_name'] = [
                    'text' => '≈ '.implode(' and ', $similarHumanFirstNames),
                    'suggested' => $similarHumanFirstNames[0],
                    'options' => $similarHumanFirstNames,
                ];
            }
        }

        if ($humanLastName !== '') {
            $similarHumanLastNames = $this->similarOptions(
                Humans::query()->where('projects_id', $projectId),
                'last_name',
                $humanLastName
            );
            if (! empty($similarHumanLastNames)) {
                $fieldWarnings['human_last_name'] = [
                    'text' => '≈ '.implode(' and ', $similarHumanLastNames),
                    'suggested' => $similarHumanLastNames[0],
                    'options' => $similarHumanLastNames,
                ];
            }
        }

        $sampleTypeExists = false;
        $existingSampleType = null;
        if ($sampleType !== '') {
            $existingSampleType = $this->findSampleTypeByFlexibleName($sampleType);
            $sampleTypeExists = $existingSampleType !== null;
            if ($sampleTypeExists) {
                $sampleType = (string) ($existingSampleType->name ?? $sampleType);
                $sampleTypeCategory = (string) ($existingSampleType->category ?? '');
            } else {
                if ($sampleTypeCategory === '') {
                    $issues[] = 'sample_type_category is required when sample_type is new';
                } elseif (! in_array($sampleTypeCategory, ['host_derived', 'non_host_derived'], true)) {
                    $issues[] = 'sample_type_category must be host_derived or non_host_derived';
                }
            }

            $similarSampleTypes = $this->similarOptions(SampleTypes::query(), 'name', $sampleType);
            if (! empty($similarSampleTypes) && ! $sampleTypeExists) {
                $fieldWarnings['sample_type'] = [
                    'text' => '≈ '.implode(' and ', $similarSampleTypes),
                    'suggested' => $similarSampleTypes[0],
                    'options' => $similarSampleTypes,
                ];
                $warnings[] = $fieldWarnings['sample_type']['text'];
            }
        }

        $validPurposes = ['Diagnostic', 'Research', 'Surveillance'];
        if ($samplingPurpose !== '' && ! in_array($samplingPurpose, $validPurposes, true)) {
            $issues[] = 'sampling_purpose must be Diagnostic, Research, or Surveillance';
        }

        $validStorageStates = ['', 'No preservative', 'Formalin', 'RNAlater'];
        if (! in_array($storageState, $validStorageStates, true)) {
            $issues[] = 'storage_state must be No preservative, Formalin, RNAlater, or empty';
        }

        $samplingSiteExists = false;
        $samplingSiteCountryExists = false;
        if ($samplingSite !== '') {
            $samplingSiteExists = SamplingSites::query()
                ->whereRaw('lower(name) = ?', [strtolower($samplingSite)])
                ->exists();
            if (! $samplingSiteExists && $samplingSiteCountry === '') {
                $issues[] = 'sampling_site_country is required when sampling_site is new';
            }
            $similarSamplingSites = $this->similarOptions(SamplingSites::query(), 'name', $samplingSite);
            if (! empty($similarSamplingSites) && ! $samplingSiteExists) {
                $fieldWarnings['sampling_site'] = [
                    'text' => '≈ '.implode(' and ', $similarSamplingSites),
                    'suggested' => $similarSamplingSites[0],
                    'options' => $similarSamplingSites,
                ];
                $warnings[] = $fieldWarnings['sampling_site']['text'];
            }
        }

        if (! $samplingSiteExists && $samplingSiteCountry !== '') {
            $samplingSiteCountryExists = Countries::query()
                ->whereRaw('lower(name) = ?', [strtolower($samplingSiteCountry)])
                ->exists();
            if (! $samplingSiteCountryExists) {
                $similarCountries = $this->similarOptions(Countries::query(), 'name', $samplingSiteCountry);
                if (! empty($similarCountries)) {
                    $fieldWarnings['sampling_site_country'] = [
                        'text' => '≈ '.implode(' and ', $similarCountries),
                        'suggested' => $similarCountries[0],
                        'options' => $similarCountries,
                    ];
                    $warnings[] = $fieldWarnings['sampling_site_country']['text'];
                }
            }
        }

        $locationExists = false;
        if ($location !== '') {
            $locationExists = Locations::query()
                ->whereRaw('lower(name) = ?', [strtolower($location)])
                ->exists();

            if (! $locationExists && $locationLab === '') {
                $issues[] = 'location_lab is required when location is new';
            }

            $similarLocations = $this->similarOptions(Locations::query(), 'name', $location);
            if (! empty($similarLocations) && ! $locationExists) {
                $fieldWarnings['location'] = [
                    'text' => '≈ '.implode(' and ', $similarLocations),
                    'suggested' => $similarLocations[0],
                    'options' => $similarLocations,
                ];
                $warnings[] = $fieldWarnings['location']['text'];
            }
        }

        $locationLabExists = false;
        if ($locationLab !== '') {
            $locationLabExists = Laboratories::query()
                ->whereRaw('lower(name) = ?', [strtolower($locationLab)])
                ->exists();
            $similarLabs = $this->similarOptions(Laboratories::query(), 'name', $locationLab);
            if (! empty($similarLabs) && ! $locationLabExists) {
                $fieldWarnings['location_lab'] = [
                    'text' => '≈ '.implode(' and ', $similarLabs),
                    'suggested' => $similarLabs[0],
                    'options' => $similarLabs,
                ];
                $warnings[] = $fieldWarnings['location_lab']['text'];
            }
        }

        $collectorExists = false;
        $collectorFirstNameExists = false;
        $collectorLastNameExists = false;
        if ($collectorEmail !== '') {
            $collectorExists = People::query()
                ->whereRaw('lower(email) = ?', [strtolower($collectorEmail)])
                ->exists();
            if (! $collectorExists) {
                if ($collectorFirstName === '' || $collectorLastName === '') {
                    $issues[] = "collector_email not found: {$collectorEmail} (provide collector_first_name and collector_last_name)";
                }
                if ($collectorFirstName !== '') {
                    $collectorFirstNameExists = People::query()
                        ->whereRaw('lower(first_name) = ?', [strtolower($collectorFirstName)])
                        ->exists();
                    $similarCollectorFirstNames = $this->similarOptions(People::query(), 'first_name', $collectorFirstName);
                    if (! empty($similarCollectorFirstNames) && ! $collectorFirstNameExists) {
                        $fieldWarnings['collector_first_name'] = [
                            'text' => '≈ '.implode(' and ', $similarCollectorFirstNames),
                            'suggested' => $similarCollectorFirstNames[0],
                            'options' => $similarCollectorFirstNames,
                        ];
                        $warnings[] = $fieldWarnings['collector_first_name']['text'];
                    }
                }
                if ($collectorLastName !== '') {
                    $collectorLastNameExists = People::query()
                        ->whereRaw('lower(last_name) = ?', [strtolower($collectorLastName)])
                        ->exists();
                    $similarCollectorLastNames = $this->similarOptions(People::query(), 'last_name', $collectorLastName);
                    if (! empty($similarCollectorLastNames) && ! $collectorLastNameExists) {
                        $fieldWarnings['collector_last_name'] = [
                            'text' => '≈ '.implode(' and ', $similarCollectorLastNames),
                            'suggested' => $similarCollectorLastNames[0],
                            'options' => $similarCollectorLastNames,
                        ];
                        $warnings[] = $fieldWarnings['collector_last_name']['text'];
                    }
                }
                $similarCollectorEmails = $this->similarEmailOptions($collectorEmail);
                if (! empty($similarCollectorEmails)) {
                    $fieldWarnings['collector_email'] = [
                        'text' => '≈ '.implode(' and ', $similarCollectorEmails),
                        'suggested' => $similarCollectorEmails[0],
                        'options' => $similarCollectorEmails,
                    ];
                    $warnings[] = $fieldWarnings['collector_email']['text'];
                }
            }
        }

        return [
            'row_number' => $rowNumber ?? 0,
            'human_first_name' => $humanFirstName,
            'human_last_name' => $humanLastName,
            'human_sex' => $humanSex,
            'human_date_of_birth' => $humanDateOfBirth,
            'human_ethnicity' => $humanEthnicity,
            'human_occupation' => $humanOccupation,
            'human_marital_status' => $humanMaritalStatus,
            'human_country' => $humanCountry,
            'human_city' => $humanCity,
            'human_province' => $humanProvince,
            'human_street' => $humanStreet,
            'human_postal_code' => $humanPostalCode,
            'human_preferred_contact_method' => $humanPreferredContactMethod,
            'human_phone' => $humanPhone,
            'human_alternate_phone' => $humanAlternatePhone,
            'human_email' => $humanEmail,
            'human_alternate_email' => $humanAlternateEmail,
            'human_national_id' => $humanNationalId,
            'human_insurance_provider' => $humanInsuranceProvider,
            'human_insurance_id' => $humanInsuranceId,
            'sample_type' => $sampleType,
            'field_label' => $fieldLabel,
            'sample_type_category' => $sampleTypeCategory,
            'date_collected' => $dateCollected,
            'sampling_purpose' => $samplingPurpose,
            'storage_state' => $storageState,
            'sampling_site' => $samplingSite,
            'sampling_site_country' => $samplingSiteCountry,
            'area' => $area,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location' => $location,
            'location_lab' => $locationLab,
            'collector_email' => $collectorEmail,
            'collector_first_name' => $collectorFirstName,
            'collector_last_name' => $collectorLastName,
            'human_exists' => $humanExists,
            'human_country_exists' => $humanCountryExists,
            'sample_type_exists' => $sampleTypeExists,
            'sampling_site_exists' => $samplingSiteExists,
            'sampling_site_country_exists' => $samplingSiteCountryExists,
            'location_exists' => $locationExists,
            'location_lab_exists' => $locationLabExists,
            'collector_exists' => $collectorExists,
            'collector_first_name_exists' => $collectorFirstNameExists,
            'collector_last_name_exists' => $collectorLastNameExists,
            'sampling_purpose_invalid' => $samplingPurpose !== '' && ! in_array($samplingPurpose, $validPurposes, true),
            'storage_state_invalid' => ! in_array($storageState, $validStorageStates, true),
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
        ];
    }

    private function normalizeSamplingPurpose(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'diagnostic' => 'Diagnostic',
            'research' => 'Research',
            'surveillance' => 'Surveillance',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function normalizeStorageState(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'no preservative' => 'No preservative',
            'formalin' => 'Formalin',
            'rnalater', 'rna later' => 'RNAlater',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function normalizeHumanSex(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function normalizePreferredContactMethod(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'phone' => 'phone',
            'email' => 'email',
            'sms' => 'sms',
            default => $value,
        };
    }

    private function sanitizeCell(string $value): string
    {
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

    private function resolveOrCreateSamplingSiteId(HumanSamplesService $service, string $name, string $countryName = ''): ?int
    {
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
        ]);

        return (int) $site->id;
    }

    private function resolveOrCreateLocationId(HumanSamplesService $service, string $locationName, string $labName = ''): ?int
    {
        $locationName = trim($locationName);
        if ($locationName === '') {
            return null;
        }

        $existing = Locations::query()
            ->whereRaw('lower(name) = ?', [strtolower($locationName)])
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $labName = trim($labName);
        if ($labName === '') {
            return null;
        }

        $lab = Laboratories::query()
            ->whereRaw('lower(name) = ?', [strtolower($labName)])
            ->first();
        if (! $lab) {
            $labId = (int) $service->check_or_create(Laboratories::class, ['name' => $labName]);
            $lab = Laboratories::find($labId);
        }

        if (! $lab) {
            return null;
        }

        $locationId = (int) $service->check_or_create(
            Locations::class,
            ['name' => $locationName],
            ['laboratories_id' => $lab->id]
        );

        return $locationId;
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

            if ($score >= $threshold) {
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
            if ($pct >= 72.0) {
                $scored[] = ['email' => $candidateEmail, 'score' => $pct];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        foreach ($scored as $entry) {
            $result[] = (string) $entry['email'];
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
        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return implode(' ', $tokens);
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

    private function nextHumanSampleSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = HumanSamples::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-HS-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-HS-(\d+)$/', (string) $code, $m);

            return isset($m[1]) ? (int) $m[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($used as $n) {
            if ($n !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
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

        $serial = 1;
        foreach ($used as $n) {
            if ($n !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function findExistingHumanByImportData(int $projectId, array $resolved, int $countryId): ?Humans
    {
        $query = Humans::query()
            ->where('projects_id', $projectId)
            ->where('countries_id', $countryId)
            ->whereRaw('lower(first_name) = ?', [strtolower((string) ($resolved['human_first_name'] ?? ''))])
            ->whereRaw('lower(last_name) = ?', [strtolower((string) ($resolved['human_last_name'] ?? ''))]);

        $dateOfBirth = (string) ($resolved['human_date_of_birth'] ?? '');
        if ($dateOfBirth !== '') {
            $query->whereDate('date_of_birth', $dateOfBirth);
        }

        $email = strtolower((string) ($resolved['human_email'] ?? ''));
        if ($email !== '') {
            $query->whereRaw('lower(email) = ?', [$email]);
        }

        $nationalId = (string) ($resolved['human_national_id'] ?? '');
        if ($nationalId !== '') {
            $query->where('national_id_hash', Humans::blindIndex($nationalId));
        }

        return $query->first();
    }

    /**
     * @return list<string>
     */
    private function requiredFields(): array
    {
        return [
            'sample_type',
            'date_collected',
            'sampling_purpose',
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
            'human_first_name' => ['human_first_name', 'human first name', 'patient_first_name', 'patient first name', 'first_name'],
            'human_last_name' => ['human_last_name', 'human last name', 'patient_last_name', 'patient last name', 'last_name'],
            'human_sex' => ['human_sex', 'patient_sex', 'sex'],
            'human_date_of_birth' => ['human_date_of_birth', 'patient_date_of_birth', 'date_of_birth', 'dob'],
            'human_ethnicity' => ['human_ethnicity', 'patient_ethnicity', 'ethnicity'],
            'human_occupation' => ['human_occupation', 'patient_occupation', 'occupation'],
            'human_marital_status' => ['human_marital_status', 'patient_marital_status', 'marital_status'],
            'human_country' => ['human_country', 'patient_country', 'human country', 'patient country'],
            'human_city' => ['human_city', 'patient_city', 'city'],
            'human_province' => ['human_province', 'patient_province', 'province'],
            'human_street' => ['human_street', 'patient_street', 'street'],
            'human_postal_code' => ['human_postal_code', 'patient_postal_code', 'postal_code'],
            'human_preferred_contact_method' => ['human_preferred_contact_method', 'preferred_contact_method', 'contact_method'],
            'human_phone' => ['human_phone', 'patient_phone', 'phone'],
            'human_alternate_phone' => ['human_alternate_phone', 'patient_alternate_phone', 'alternate_phone'],
            'human_email' => ['human_email', 'patient_email', 'email'],
            'human_alternate_email' => ['human_alternate_email', 'patient_alternate_email', 'alternate_email'],
            'human_national_id' => ['human_national_id', 'patient_national_id', 'national_id'],
            'human_insurance_provider' => ['human_insurance_provider', 'patient_insurance_provider', 'insurance_provider'],
            'human_insurance_id' => ['human_insurance_id', 'patient_insurance_id', 'insurance_id'],
            'sample_type' => ['sample_type', 'sample type', 'type', 'sampletype'],
            'field_label' => ['field_label', 'field label', 'label', 'sample_label', 'sample label'],
            'sample_type_category' => ['sample_type_category', 'sample category', 'category'],
            'date_collected' => ['date_collected', 'date', 'collection_date', 'collected_date'],
            'sampling_purpose' => ['sampling_purpose', 'sample_purpose', 'purpose'],
            'storage_state' => ['storage_state', 'preservant', 'preservative'],
            'sampling_site' => ['sampling_site', 'sampling site', 'site'],
            'sampling_site_country' => ['sampling_site_country', 'sampling site country', 'site_country', 'country'],
            'area' => ['area', 'sampling_area'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'lon', 'lng'],
            'location' => ['location', 'storage_location', 'location_name'],
            'location_lab' => ['location_lab', 'lab', 'laboratory', 'laboratory_name'],
            'collector_email' => ['collector_email', 'collector', 'collected_by_email', 'people_email'],
            'collector_first_name' => ['collector_first_name', 'collector first name', 'collector_first'],
            'collector_last_name' => ['collector_last_name', 'collector last name', 'collector_last'],
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int, from: int, to: int}
     */
    public function previewPage(): array
    {
        if (! $this->cacheKey) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0];
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0];
        }

        $total = count($rows);
        $page = max(1, (int) $this->page);
        $perPage = max(10, min(200, (int) $this->perPage));
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
        ];
    }

    public function render()
    {
        return view('livewire.imports.human-samples-import', [
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
            'imports:human_samples:template_options:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'countries' => $this->optionPreview(Countries::query()->orderBy('name'), 'name'),
                    'sample_types' => $this->optionPreview(SampleTypes::query()->orderBy('name'), 'name'),
                    'sampling_sites' => $this->optionPreview(SamplingSites::query()->orderBy('name'), 'name'),
                    'laboratories' => $this->optionPreview(Laboratories::query()->orderBy('name'), 'name'),
                    'locations' => $this->optionPreview(Locations::query()->orderBy('name'), 'name'),
                    'collector_emails' => $this->optionPreview(
                        $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                        'email'
                    ),
                ];
            }
        );

        $aliases = $this->synonymsByField();

        $samplingPurposes = ['Diagnostic', 'Research', 'Surveillance'];
        $storageStates = ['No preservative', 'Formalin', 'RNAlater'];
        $contactMethods = ['phone', 'email', 'sms'];

        $columns = [
            [
                'header' => 'human_first_name',
                'field' => 'human_first_name',
                'required' => 'required',
                'description' => 'Patient first name. Used (with last name and country) to match or create the human record.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['human_first_name'] ?? [],
                'create_policy' => 'Links to existing Humans by exact match (project + name + country + optional DOB/email/national_id); otherwise creates a new human.',
                'create_notes' => '',
                'example' => 'Anna',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'human_last_name',
                'field' => 'human_last_name',
                'required' => 'required',
                'description' => 'Patient last name.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['human_last_name'] ?? [],
                'create_policy' => 'Used to match/create Humans.',
                'create_notes' => '',
                'example' => 'Rossi',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'human_sex',
                'field' => 'human_sex',
                'required' => 'optional',
                'description' => 'Patient sex (optional).',
                'format' => 'Male or Female (case-insensitive).',
                'accepted' => ['Male', 'Female'],
                'aliases' => $aliases['human_sex'] ?? [],
                'create_policy' => 'Optional, but if provided must be Male/Female.',
                'create_notes' => '',
                'example' => 'Female',
                'options' => ['Male', 'Female'],
                'options_total' => 2,
            ],
            [
                'header' => 'human_date_of_birth',
                'field' => 'human_date_of_birth',
                'required' => 'optional',
                'description' => 'Patient date of birth (optional). Helps matching existing humans.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['human_date_of_birth'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '1991-06-10',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'human_country',
                'field' => 'human_country',
                'required' => 'required',
                'description' => 'Patient country.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['human_country'] ?? [],
                'create_policy' => 'Links to existing Countries by name; otherwise creates the country record.',
                'create_notes' => '',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'sample_type',
                'field' => 'sample_type',
                'required' => 'required',
                'description' => 'Sample type for the human sample.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['sample_type'] ?? [],
                'create_policy' => 'Links to existing SampleTypes by flexible match; otherwise creates a new sample type.',
                'create_notes' => 'If new, sample_type_category is required.',
                'example' => 'Blood',
                'options' => $options['sample_types']['values'],
                'options_total' => $options['sample_types']['total'],
            ],
            [
                'header' => 'sample_type_category',
                'field' => 'sample_type_category',
                'required' => 'conditional',
                'description' => 'Category for a newly created sample type.',
                'format' => 'host_derived or non_host_derived.',
                'accepted' => ['host_derived', 'non_host_derived'],
                'aliases' => $aliases['sample_type_category'] ?? [],
                'create_policy' => 'Required only when sample_type is new.',
                'create_notes' => '',
                'example' => 'host_derived',
                'options' => ['host_derived', 'non_host_derived'],
                'options_total' => 2,
            ],
            [
                'header' => 'field_label',
                'field' => 'field_label',
                'required' => 'optional',
                'description' => 'Optional patient field label (stored on the human record when creating a new human, or filled if the existing human field_label is empty).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['field_label'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => 'HS-Field-01',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'date_collected',
                'field' => 'date_collected',
                'required' => 'required',
                'description' => 'Collection date for the sample.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_collected'] ?? [],
                'create_policy' => 'Required.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sampling_purpose',
                'field' => 'sampling_purpose',
                'required' => 'required',
                'description' => 'Sampling purpose.',
                'format' => 'One of the accepted values.',
                'accepted' => $samplingPurposes,
                'aliases' => $aliases['sampling_purpose'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Diagnostic',
                'options' => $samplingPurposes,
                'options_total' => count($samplingPurposes),
            ],
            [
                'header' => 'sampling_site',
                'field' => 'sampling_site',
                'required' => 'required',
                'description' => 'Sampling site name.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site'] ?? [],
                'create_policy' => 'Links to existing SamplingSites by name; otherwise creates a new sampling site.',
                'create_notes' => 'If new, sampling_site_country is required.',
                'example' => 'Pretoria Clinic',
                'options' => $options['sampling_sites']['values'],
                'options_total' => $options['sampling_sites']['total'],
            ],
            [
                'header' => 'sampling_site_country',
                'field' => 'sampling_site_country',
                'required' => 'conditional',
                'description' => 'Country for a newly created sampling site.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site_country'] ?? [],
                'create_policy' => 'Required only when sampling_site is new. Links to Countries by name; otherwise creates the country record.',
                'create_notes' => '',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'location',
                'field' => 'location',
                'required' => 'required',
                'description' => 'Storage location name.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['location'] ?? [],
                'create_policy' => 'Links to existing Locations by name; otherwise creates a new location.',
                'create_notes' => 'If new, location_lab is required.',
                'example' => 'Freezer A Shelf 2',
                'options' => $options['locations']['values'],
                'options_total' => $options['locations']['total'],
            ],
            [
                'header' => 'location_lab',
                'field' => 'location_lab',
                'required' => 'conditional',
                'description' => 'Laboratory for a newly created location.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['location_lab'] ?? [],
                'create_policy' => 'Required only when location is new. Links to existing Laboratories by name; otherwise creates the lab record.',
                'create_notes' => '',
                'example' => 'Central Lab',
                'options' => $options['laboratories']['values'],
                'options_total' => $options['laboratories']['total'],
            ],
            [
                'header' => 'collector_email',
                'field' => 'collector_email',
                'required' => 'required',
                'description' => 'Collector email address.',
                'format' => 'Email.',
                'accepted' => [],
                'aliases' => $aliases['collector_email'] ?? [],
                'create_policy' => 'Links to existing People by email; otherwise creates a new person.',
                'create_notes' => 'If new, collector_first_name and collector_last_name are required.',
                'example' => 'collector@example.org',
                'options' => $options['collector_emails']['values'],
                'options_total' => $options['collector_emails']['total'],
            ],
            [
                'header' => 'collector_first_name',
                'field' => 'collector_first_name',
                'required' => 'conditional',
                'description' => 'Collector first name (for new collector_email).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_first_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Carla',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'collector_last_name',
                'field' => 'collector_last_name',
                'required' => 'conditional',
                'description' => 'Collector last name (for new collector_email).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_last_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Cossu',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'storage_state',
                'field' => 'storage_state',
                'required' => 'optional',
                'description' => 'Optional storage state / preservant.',
                'format' => 'Empty or one of the accepted values.',
                'accepted' => $storageStates,
                'aliases' => $aliases['storage_state'] ?? [],
                'create_policy' => 'Optional. If provided, must match accepted values.',
                'create_notes' => '',
                'example' => 'Formalin',
                'options' => $storageStates,
                'options_total' => count($storageStates),
            ],
            [
                'header' => 'human_preferred_contact_method',
                'field' => 'human_preferred_contact_method',
                'required' => 'optional',
                'description' => 'Optional preferred contact method.',
                'format' => 'phone, email, or sms.',
                'accepted' => $contactMethods,
                'aliases' => $aliases['human_preferred_contact_method'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => 'email',
                'options' => $contactMethods,
                'options_total' => count($contactMethods),
            ],
            [
                'header' => 'human_phone',
                'field' => 'human_phone',
                'required' => 'optional',
                'description' => 'Optional phone number.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['human_phone'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => '+27123456789',
                'options' => [],
                'options_total' => 0,
            ],
        ];

        return [
            'columns' => $columns,
        ];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreview(Builder $query, string $column, int $limit = 10): array
    {
        $base = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $base)->distinct()->count($column);
        $values = (clone $base)
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();

        return ['values' => $values, 'total' => $total];
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);

        $samplingPurposes = ['Diagnostic', 'Research', 'Surveillance'];
        if ($field === 'sampling_purpose') {
            return [
                'title' => 'Sampling purpose',
                'values' => $samplingPurposes,
                'total' => count($samplingPurposes),
                'truncated' => false,
            ];
        }

        $storageStates = ['No preservative', 'Formalin', 'RNAlater'];
        if ($field === 'storage_state') {
            return [
                'title' => 'Storage state',
                'values' => $storageStates,
                'total' => count($storageStates),
                'truncated' => false,
            ];
        }

        $contactMethods = ['phone', 'email', 'sms'];
        if ($field === 'human_preferred_contact_method') {
            return [
                'title' => 'Preferred contact methods',
                'values' => $contactMethods,
                'total' => count($contactMethods),
                'truncated' => false,
            ];
        }

        if ($field === 'sample_type_category') {
            $categories = ['host_derived', 'non_host_derived'];

            return [
                'title' => 'Sample type categories',
                'values' => $categories,
                'total' => count($categories),
                'truncated' => false,
            ];
        }

        if ($field === 'human_sex') {
            $sexes = ['Male', 'Female'];

            return [
                'title' => 'Human sex values',
                'values' => $sexes,
                'total' => count($sexes),
                'truncated' => false,
            ];
        }

        $max = 1200;

        return match ($field) {
            'human_country', 'sampling_site_country' => $this->templateOptionsFromQuery(Countries::query(), 'name', 'Countries', $max),
            'sample_type' => $this->templateOptionsFromQuery(SampleTypes::query(), 'name', 'Sample types', $max),
            'sampling_site' => $this->templateOptionsFromQuery(SamplingSites::query(), 'name', 'Sampling sites', $max),
            'location' => $this->templateOptionsFromQuery(Locations::query(), 'name', 'Locations', $max),
            'location_lab' => $this->templateOptionsFromQuery(Laboratories::query(), 'name', 'Laboratories', $max),
            'collector_email' => $this->templateOptionsFromQuery(
                $this->projectPeopleEmailsQuery($projectId),
                'email',
                'Collector emails',
                $max
            ),
            default => [
                'title' => 'Values',
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

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsFromQuery(Builder $query, string $column, string $title, int $max = 1200): array
    {
        $base = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $base)->distinct()->count($column);

        $values = (clone $base)
            ->distinct()
            ->orderBy($column)
            ->limit($max)
            ->pluck($column)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();

        $truncated = $total > count($values);

        return [
            'title' => $title,
            'values' => $values,
            'total' => $total,
            'truncated' => $truncated,
        ];
    }
}

<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\Animals;
use App\Models\AnimalSpecies;
use App\Models\Countries;
use App\Models\Humans;
use App\Models\Organizations;
use App\Models\Projects;
use App\Services\AnimalSamplesService;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class AnimalsImport extends PlainComponent
{
    use WithFileUploads;

    #[Validate('required|file|mimes:csv,txt,xlsx,xls|max:20480')]
    public $file;

    public string $status = 'idle'; // idle|preview|imported|error

    public array $globalIssues = [];

    /** @var list<string> */
    public array $headers = [];

    /** @var array<string,int> */
    public array $fieldToIndex = [];

    public int $page = 1;

    public int $perPage = 25;

    public ?string $cacheKey = null;

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->buildPreview();
    }

    public function buildPreview(): void
    {
        $this->globalIssues = [];

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import animals in this project (viewer accounts are read-only).';
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

        $this->cacheKey = "imports:animals:{$projectId}:".bin2hex(random_bytes(8));
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
        $this->status = 'idle';
        $this->page = 1;
    }

    public function import(): void
    {
        $this->globalIssues = [];

        if ($this->status !== 'preview' || ! $this->cacheKey) {
            $this->globalIssues[] = 'Upload a file and fix issues before importing.';
            $this->status = 'error';

            return;
        }

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import animals in this project (viewer accounts are read-only).';
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
        $rollbackSignal = '__animals_bulk_import_rollback__';

        try {
            DB::transaction(function () use ($rows, $service, $project, &$created, &$errors, $rollbackSignal): void {
                $nextSerial = $this->nextAnimalSerialForProject($project->id, $project->code);

                foreach ($rows as $i => $row) {
                    $rowNumber = $i + 2; // 1-based, plus header row
                    $resolved = $this->resolveRow($row);

                    $rowIssues = $resolved['issues'];
                    if (! empty($rowIssues)) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $rowIssues);

                        continue;
                    }

                    $speciesId = $service->check_or_create(
                        AnimalSpecies::class,
                        ['name_common' => $resolved['animal_species']],
                        array_filter([
                            'family' => $resolved['animal_species_family'] ?: null,
                            'name_scientific' => $resolved['animal_species_scientific'] ?: null,
                        ], fn ($v) => $v !== null)
                    );

                    $ownerType = $resolved['owner_type'] === 'individual' ? Humans::class : Organizations::class;
                    $ownerId = null;

                    if ($resolved['owner_type'] === 'individual') {
                        $human = Humans::query()
                            ->where('projects_id', $project->id)
                            ->where('code', $resolved['owner_human_code'])
                            ->first();
                        if (! $human) {
                            $errors++;
                            $this->globalIssues[] = "Row {$rowNumber}: owner_human_code not found in this project: {$resolved['owner_human_code']}";

                            continue;
                        }
                        $ownerId = $human->id;
                    } else {
                        $org = Organizations::query()
                            ->where('name', $resolved['owner_organization_name'])
                            ->first();

                        if (! $org) {
                            $countryId = $service->check_or_create(Countries::class, ['name' => $resolved['owner_organization_country']]);
                            $orgId = $service->check_or_create(
                                Organizations::class,
                                ['name' => $resolved['owner_organization_name']],
                                ['countries_id' => $countryId, 'type' => $resolved['owner_organization_type'] ?: 'company']
                            );
                            $org = Organizations::find($orgId);
                        }

                        $ownerId = $org?->id;
                    }

                    if (! $ownerId) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve owner.";

                        continue;
                    }

                    $animalCode = $project->code.'-AN-'.$nextSerial;
                    $nextSerial++;

                    Animals::query()->create([
                        'code' => $animalCode,
                        'animal_species_id' => $speciesId,
                        'field_label' => $resolved['field_label'],
                        'sex' => $resolved['sex'],
                        'age' => $resolved['age'],
                        'owner_type' => $ownerType,
                        'owner_id' => $ownerId,
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
        $successMessage = "{$created} animals imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'animal_created',
            'Bulk animals imported',
            $successMessage,
            '/animals/list',
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
     *   animal_species: string,
     *   animal_species_family: string,
     *   animal_species_scientific: string,
     *   field_label: string,
     *   sex: string,
     *   age: string,
     *   owner_type: string,
     *   owner_human_code: string,
     *   owner_organization_name: string,
     *   owner_organization_country: string,
     *   owner_organization_type: string,
     *   issues: list<string>
     * }
     */
    private function resolveRow(array $row): array
    {
        $get = function (string $field) use ($row): string {
            $idx = $this->fieldToIndex[$field] ?? null;
            if ($idx === null) {
                return '';
            }

            return isset($row[$idx]) ? trim((string) $row[$idx]) : '';
        };

        $animalSpecies = $get('animal_species');
        $fieldLabel = $get('field_label');
        $sex = ucfirst(strtolower($get('sex')));
        $age = $get('age');
        $ownerType = strtolower($get('owner_type'));
        $ownerHumanCode = $get('owner_human_code');
        $ownerOrgName = $get('owner_organization_name');
        $ownerOrgCountry = $get('owner_organization_country');

        $issues = [];

        if ($animalSpecies === '') {
            $issues[] = 'animal_species is required';
        }
        if ($fieldLabel === '') {
            $issues[] = 'field_label is required';
        }
        if (! in_array($sex, ['Male', 'Female', 'Na', 'N/a', 'NA'], true)) {
            if ($sex === 'Na' || $sex === 'N/a') {
                $sex = 'NA';
            }
        }
        if (! in_array($sex, ['Male', 'Female', 'NA'], true)) {
            $issues[] = "Invalid sex: {$sex} (expected Male/Female/NA)";
        }

        $ageNorm = strtolower(trim($age));
        $ageMap = [
            'juvenile' => 'Juvenile',
            'sub-adult' => 'Sub-adult',
            'subadult' => 'Sub-adult',
            'adult' => 'Adult',
            'na' => 'NA',
            'n/a' => 'NA',
        ];
        $age = $ageMap[$ageNorm] ?? $age;

        if (! in_array($age, ['Juvenile', 'Sub-adult', 'Adult', 'NA'], true)) {
            $issues[] = "Invalid age: {$age} (expected Juvenile/Sub-adult/Adult/NA)";
        }

        if (! in_array($ownerType, ['individual', 'organization'], true)) {
            $issues[] = "Invalid owner_type: {$ownerType} (expected individual/organization)";
        } elseif ($ownerType === 'individual') {
            if ($ownerHumanCode === '') {
                $issues[] = 'owner_human_code is required when owner_type=individual';
            }
        } else {
            if ($ownerOrgName === '') {
                $issues[] = 'owner_organization_name is required when owner_type=organization';
            }
            // If org doesn't exist, we need a country to create it
            if ($ownerOrgName !== '' && $ownerOrgCountry === '') {
                $exists = Organizations::query()->where('name', $ownerOrgName)->exists();
                if (! $exists) {
                    $issues[] = 'owner_organization_country is required to create a new organization';
                }
            }
        }

        return [
            'animal_species' => $animalSpecies,
            'animal_species_family' => $get('animal_species_family'),
            'animal_species_scientific' => $get('animal_species_scientific'),
            'field_label' => $fieldLabel,
            'sex' => $sex,
            'age' => $age,
            'owner_type' => $ownerType,
            'owner_human_code' => $ownerHumanCode,
            'owner_organization_name' => $ownerOrgName,
            'owner_organization_country' => $ownerOrgCountry,
            'owner_organization_type' => $get('owner_organization_type'),
            'issues' => $issues,
        ];
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
     * @return list<string>
     */
    private function requiredFields(): array
    {
        return [
            'animal_species',
            'field_label',
            'sex',
            'age',
            'owner_type',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'animal_species' => ['animal_species', 'species', 'species_common', 'name_common', 'animal species'],
            'animal_species_family' => ['family', 'species_family', 'animal_species_family'],
            'animal_species_scientific' => ['name_scientific', 'species_scientific', 'animal_species_scientific', 'scientific_name'],
            'field_label' => ['field_label', 'field_id', 'field id', 'fieldlabel', 'label'],
            'sex' => ['sex', 'gender'],
            'age' => ['age', 'age_class', 'age class'],
            'owner_type' => ['owner_type', 'owner type'],
            'owner_human_code' => ['owner_human_code', 'owner_code', 'owner_human', 'human_code', 'owner person code'],
            'owner_organization_name' => ['owner_organization_name', 'owner_org', 'owner_organization', 'organization', 'org_name', 'owner organization'],
            'owner_organization_country' => ['owner_organization_country', 'org_country', 'organization_country', 'country'],
            'owner_organization_type' => ['owner_organization_type', 'org_type', 'organization_type', 'type'],
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
        foreach ($slice as $row) {
            $mapped[] = $this->resolveRow(is_array($row) ? $row : []);
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
        return view('livewire.imports.animals-import');
    }
}

<?php

use App\Http\Controllers\Admin\AnnouncementsAdminController;
use App\Http\Controllers\Admin\GlobalLookupAdminController;
use App\Http\Controllers\Admin\PublicationReviewAdminController;
use App\Http\Controllers\AnimalHealthController;
use App\Http\Controllers\AnimalMedicationController;
use App\Http\Controllers\AnimalSamplesController;
use App\Http\Controllers\AnimalSamplesCreateSelectionsController;
use App\Http\Controllers\AnimalSamplesDashboardMapPointsController;
use App\Http\Controllers\AnimalSamplesDashboardModalTablesController;
use App\Http\Controllers\AnimalsController;
use App\Http\Controllers\AnimalSpeciesController;
use App\Http\Controllers\AnimalVaccinationController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\BoxesController;
use App\Http\Controllers\CulturesController;
use App\Http\Controllers\CulturesCreateSelectionsController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\EnvironmentSamplesController;
use App\Http\Controllers\EnvironmentSamplesDashboardMapPointsController;
use App\Http\Controllers\EnvironmentSamplesDashboardModalTablesController;
use App\Http\Controllers\ExperimentsController;
use App\Http\Controllers\ExperimentsCreateTubesController;
use App\Http\Controllers\ExperimentsDashboardMapPointsController;
use App\Http\Controllers\FieldSamplesProcessSelectionsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HumanSamplesController;
use App\Http\Controllers\HumanSamplesCreateSelectionsController;
use App\Http\Controllers\HumanSamplesDashboardMapPointsController;
use App\Http\Controllers\HumanSamplesDashboardModalTablesController;
use App\Http\Controllers\HumansController;
use App\Http\Controllers\LaboratoriesController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\MetaCreateSelectionsController;
use App\Http\Controllers\MicroplasticsController;
use App\Http\Controllers\MicroplasticsCreateSelectionsController;
use App\Http\Controllers\MicroplasticsDashboardMapPointsController;
use App\Http\Controllers\MicroplasticsProtocolsController;
use App\Http\Controllers\NameValidationController;
use App\Http\Controllers\NewPasswordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NucleicAcidsController;
use App\Http\Controllers\NucleicAcidsCreateSelectionsController;
use App\Http\Controllers\NucleicAcidsDashboardMapPointsController;
use App\Http\Controllers\NucleicAcidsDashboardModalTablesController;
use App\Http\Controllers\NucleicProtocolsController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\ParasiteSamplesController;
use App\Http\Controllers\ParasiteSamplesCreateSelectionsController;
use App\Http\Controllers\ParasiteSamplesDashboardMapPointsController;
use App\Http\Controllers\ParasiteSamplesDashboardModalTablesController;
use App\Http\Controllers\ParasitesDissectionController;
use App\Http\Controllers\ParasitesDissectionCreateSelectionsController;
use App\Http\Controllers\ParasiteSpeciesController;
use App\Http\Controllers\ParasiteStatusController;
use App\Http\Controllers\PasswordResetLinkController;
use App\Http\Controllers\PathogensController;
use App\Http\Controllers\PathogensProtocolsController;
use App\Http\Controllers\PoolsController;
use App\Http\Controllers\PoolsCreateSelectionsController;
use App\Http\Controllers\PoolsDashboardMapPointsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ProjectSelectionController;
use App\Http\Controllers\ProtocolsController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\SamplingSitesController;
use App\Http\Controllers\SequencesController;
use App\Http\Controllers\SequencesCreateSelectionsController;
use App\Http\Controllers\SequencesDashboardMapPointsController;
use App\Http\Controllers\SequencesDashboardModalTablesController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\StudiesController;
use App\Http\Controllers\SubProjectsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TubePositionsCreateSelectionsController;
use App\Http\Controllers\TubePositionsDashboardMapPointsController;
use App\Http\Controllers\TubesController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WebAuthn\PasskeyController;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;
use App\Http\Middleware\RequireProjectSelection;
use App\Livewire\AnimalHealthIndex;
use App\Livewire\AnimalMedicationIndex;
use App\Livewire\AnimalProfile;
use App\Livewire\AnimalSampleProfile;
use App\Livewire\AnimalSamplesDashboard;
use App\Livewire\AnimalSamplesIndex;
use App\Livewire\AnimalsDashboard;
use App\Livewire\AnimalsIndex;
use App\Livewire\AnimalVaccinationIndex;
use App\Livewire\BoxContents;
use App\Livewire\BoxPositionsList;
use App\Livewire\CultureProfile;
use App\Livewire\CulturesDashboard;
use App\Livewire\CulturesIndex;
use App\Livewire\EnvironmentSampleProfile;
use App\Livewire\EnvironmentSamplesDashboard;
use App\Livewire\EnvironmentSamplesIndex;
use App\Livewire\ExperimentProfile;
use App\Livewire\ExperimentsDashboard;
use App\Livewire\ExperimentsIndex;
use App\Livewire\ExperimentsStatistics;
use App\Livewire\ExperimentsStatisticsDetailed;
use App\Livewire\ExperimentsStatisticsOverview;
use App\Livewire\FundingProfile;
use App\Livewire\HumanProfile;
use App\Livewire\HumanSampleProfile;
use App\Livewire\HumanSamplesDashboard;
use App\Livewire\HumanSamplesIndex;
use App\Livewire\MetaAnimalIndex;
use App\Livewire\MetaDashboard;
use App\Livewire\MetaEnvironmentIndex;
use App\Livewire\MetaHumanIndex;
use App\Livewire\MetaParasiteIndex;
use App\Livewire\MicroplasticProfile;
use App\Livewire\MicroplasticsDashboard;
use App\Livewire\MicroplasticsIndex;
use App\Livewire\MyProjects;
use App\Livewire\NucleicAcidProfile;
use App\Livewire\NucleicAcidsDashboard;
use App\Livewire\NucleicAcidsIndex;
use App\Livewire\ParasiteProfile;
use App\Livewire\ParasiteSampleProfile;
use App\Livewire\ParasiteSamplesDashboard;
use App\Livewire\ParasiteSamplesIndex;
use App\Livewire\ParasitesIndex;
use App\Livewire\PathogenProfile;
use App\Livewire\PoolProfile;
use App\Livewire\PoolsDashboard;
use App\Livewire\PoolsIndex;
use App\Livewire\ProjectProfile;
use App\Livewire\ProtocolProfile;
use App\Livewire\PublishData;
use App\Livewire\SequenceProfile;
use App\Livewire\SequencesDashboard;
use App\Livewire\SequencesIndex;
use App\Livewire\StudyProfile;
use App\Livewire\TubePositionsDashboard;
use App\Livewire\TubePositionsIndex;
use App\Livewire\TubeProfile;
use App\Livewire\TubeRequestsManager;
use App\Livewire\TubesList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared middleware stacks
|--------------------------------------------------------------------------
*/
$requireProjectSelection = RequireProjectSelection::class;
$authProject = ['auth', $requireProjectSelection, 'require.project.read'];
$authProjectWrite = ['auth', $requireProjectSelection, 'require.project.read', 'require.project.write'];

/*
|--------------------------------------------------------------------------
| Small route helpers (keep URIs/names identical, reduce repetition)
|--------------------------------------------------------------------------
*/
$namedGet = static function (string $uri, array $action, string $name, array $middleware = []) {
    return Route::get($uri, $action)->name($name)->middleware($middleware);
};
$namedPost = static function (string $uri, array $action, string $name, array $middleware = []) {
    return Route::post($uri, $action)->name($name)->middleware($middleware);
};

Route::get('/', [HomeController::class, 'index'])->middleware(['auth']);

Route::view('/samples', 'samples.index')->middleware($requireProjectSelection);

// Human samples
Route::prefix('samples/humans')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.humans.index')->name('human-samples.index')->middleware($requireProjectSelection);
    Route::get('/create', [HumanSamplesController::class, 'create'])->middleware($authProjectWrite);
    Route::get('/create/humans', [HumanSamplesCreateSelectionsController::class, 'humans'])
        ->name('humans.create.humans')
        ->middleware($authProjectWrite);
    Route::get('/create/humans/search', [HumanSamplesCreateSelectionsController::class, 'humansSearch'])
        ->name('humans.create.humans.search')
        ->middleware($authProjectWrite);
    Route::get('/list', HumanSamplesIndex::class)->middleware($authProject);
    Route::get('/dashboard', HumanSamplesDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', HumanSamplesDashboardMapPointsController::class)
        ->name('humans.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples', [HumanSamplesDashboardModalTablesController::class, 'samples'])
        ->name('humans.dashboard.modal.samples')
        ->middleware($authProject);
    Route::post('/', [HumanSamplesController::class, 'store'])->name('human-samples.store')->middleware($authProjectWrite);
    Route::get('/{code}', HumanSampleProfile::class)->middleware($authProject);
});

// Humans
Route::prefix('humans')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'humans.index')->name('humans.index')->middleware($requireProjectSelection);
    Route::get('/create', [HumansController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [HumansController::class, 'store'])->name('humans.store')->middleware($authProjectWrite);
    Route::get('/{code}', HumanProfile::class)->name('humans.profile')->middleware($authProject);
});

// Animals
Route::prefix('animals')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'animals.index')->name('animals.index')->middleware($requireProjectSelection);
    Route::get('/create', [AnimalsController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [AnimalsController::class, 'store'])->name('animals.store')->middleware($authProjectWrite);
    Route::get('/list', AnimalsIndex::class)->name('animals.list')->middleware($authProject);
    Route::get('/dashboard', AnimalsDashboard::class)->middleware($requireProjectSelection);
});

// Animal Species
Route::prefix('animals/species')->group(function () use ($authProjectWrite) {
    Route::get('/create', [AnimalSpeciesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [AnimalSpeciesController::class, 'store'])->name('animals.species.store')->middleware($authProjectWrite);
    Route::post('/check-duplicate', [AnimalSpeciesController::class, 'checkDuplicate'])
        ->name('animals.species.check-duplicate')
        ->middleware($authProjectWrite);
});

Route::post('/validation/name-check', [NameValidationController::class, 'check'])
    ->name('validation.name-check')
    ->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Animal samples
Route::prefix('samples/animals')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.animals.index')->name('animal-samples.index')->middleware($requireProjectSelection);
    Route::get('/create', [AnimalSamplesController::class, 'create'])->middleware($authProjectWrite);
    Route::get('/create/animals', [AnimalSamplesCreateSelectionsController::class, 'animals'])
        ->name('animals.create.animals')
        ->middleware($authProjectWrite);
    Route::get('/create/animals/search', [AnimalSamplesCreateSelectionsController::class, 'animalsSearch'])
        ->name('animals.create.animals.search')
        ->middleware($authProjectWrite);
    Route::post('/', [AnimalSamplesController::class, 'store'])->name('animal-samples.store')->middleware($authProjectWrite);
    Route::get('/list', AnimalSamplesIndex::class)->name('animal-samples.list')->middleware($authProject);
    Route::get('/dashboard', AnimalSamplesDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', AnimalSamplesDashboardMapPointsController::class)
        ->name('animals.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples', [AnimalSamplesDashboardModalTablesController::class, 'samples'])
        ->name('animals.dashboard.modal.samples')
        ->middleware($authProject);
    Route::get('/dashboard/modal/animals', [AnimalSamplesDashboardModalTablesController::class, 'animals'])
        ->name('animals.dashboard.modal.animals')
        ->middleware($authProject);
    Route::get('/dashboard/modal/species', [AnimalSamplesDashboardModalTablesController::class, 'species'])
        ->name('animals.dashboard.modal.species')
        ->middleware($authProject);
    Route::get('/dashboard/modal/sites', [AnimalSamplesDashboardModalTablesController::class, 'sites'])
        ->name('animals.dashboard.modal.sites')
        ->middleware($authProject);
    Route::get('/dashboard/modal/types', [AnimalSamplesDashboardModalTablesController::class, 'types'])
        ->name('animals.dashboard.modal.types')
        ->middleware($authProject);
    Route::get('/process', [TubesController::class, 'create_animals'])->name('animals.process')->middleware($authProjectWrite);
    Route::post('/process', [TubesController::class, 'store_animals'])->middleware($authProjectWrite);
    Route::get('/{code}', AnimalSampleProfile::class)->middleware($authProject);
});

// Animal health
Route::prefix('samples/animals/health')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.animals.health.index')->name('animalhealth.index')->middleware($requireProjectSelection);
    Route::get('/create', [AnimalHealthController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [AnimalHealthController::class, 'store'])->name('animalhealth.store')->middleware($authProjectWrite);
    Route::get('/list', AnimalHealthIndex::class)->name('animalhealth.list')->middleware($authProject);
    Route::view('/dashboard', 'samples.animals.health.dashboard')->middleware($requireProjectSelection);
});

// Animal medication
Route::prefix('samples/animals/medication')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.animals.medication.index')->name('animalmedication.index')->middleware($requireProjectSelection);
    Route::get('/create', [AnimalMedicationController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [AnimalMedicationController::class, 'store'])->name('animalmedication.store')->middleware($authProjectWrite);
    Route::get('/list', AnimalMedicationIndex::class)->name('animalmedication.list')->middleware($authProject);
    Route::view('/dashboard', 'samples.animals.medication.dashboard')->middleware($requireProjectSelection);
});

// Animal vaccination
Route::prefix('samples/animals/vaccination')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.animals.vaccination.index')->name('animalvaccination.index')->middleware($requireProjectSelection);
    Route::get('/create', [AnimalVaccinationController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [AnimalVaccinationController::class, 'store'])->name('animalvaccination.store')->middleware($authProjectWrite);
    Route::get('/list', AnimalVaccinationIndex::class)->name('animalvaccination.list')->middleware($authProject);
    Route::view('/dashboard', 'samples.animals.vaccination.dashboard')->middleware($requireProjectSelection);
});

// Environmental samples
Route::prefix('samples/environment')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.environment.index')->name('environments.index')->middleware($requireProjectSelection);
    Route::get('/create', [EnvironmentSamplesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [EnvironmentSamplesController::class, 'store'])->name('environments.store')->middleware($authProjectWrite);
    Route::get('/list', EnvironmentSamplesIndex::class)->name('environments.list')->middleware($authProject);
    Route::get('/dashboard', EnvironmentSamplesDashboard::class)->middleware($requireProjectSelection);
    Route::get('/dashboard/map-points', EnvironmentSamplesDashboardMapPointsController::class)
        ->name('environment.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples', [EnvironmentSamplesDashboardModalTablesController::class, 'samples'])
        ->name('environment.dashboard.modal.samples')
        ->middleware($authProject);
    Route::get('/{code}', EnvironmentSampleProfile::class)->middleware($authProject);
});

// Parasite samples
Route::prefix('samples/parasites')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.parasites.index')->name('parasites.index')->middleware($requireProjectSelection);
    Route::get('/create', [ParasiteSamplesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [ParasiteSamplesController::class, 'store'])->name('parasites.store')->middleware($authProjectWrite);
    Route::get('/dissection/create', [ParasitesDissectionController::class, 'create'])
        ->name('parasites.dissection.create')
        ->middleware($authProjectWrite);
    Route::post('/dissection', [ParasitesDissectionController::class, 'store'])
        ->name('parasites.dissection.store')
        ->middleware($authProjectWrite);
    Route::patch('/{parasite}/status', [ParasiteStatusController::class, 'update'])
        ->name('parasites.status.update')
        ->middleware($authProjectWrite);
    Route::get('/dissection/parasites', [ParasitesDissectionCreateSelectionsController::class, 'parasites'])
        ->name('parasites.dissection.parasites')
        ->middleware($authProject);
    Route::get('/dissection/parasites/search', [ParasitesDissectionCreateSelectionsController::class, 'parasitesSearch'])
        ->name('parasites.dissection.parasites.search')
        ->middleware($authProject);
    Route::get('/list', ParasiteSamplesIndex::class)->middleware($authProject);
    Route::get('/dashboard', ParasiteSamplesDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', ParasiteSamplesDashboardMapPointsController::class)
        ->name('parasites.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples', [ParasiteSamplesDashboardModalTablesController::class, 'all'])
        ->name('parasites.dashboard.modal.samples')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples/human', [ParasiteSamplesDashboardModalTablesController::class, 'human'])
        ->name('parasites.dashboard.modal.samples.human')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples/animal', [ParasiteSamplesDashboardModalTablesController::class, 'animal'])
        ->name('parasites.dashboard.modal.samples.animal')
        ->middleware($authProject);
    Route::get('/dashboard/modal/samples/environment', [ParasiteSamplesDashboardModalTablesController::class, 'environment'])
        ->name('parasites.dashboard.modal.samples.environment')
        ->middleware($authProject);
    Route::get('/process/list', ParasiteSamplesIndex::class)->middleware($authProject);
    Route::get('/create/samples/human', [ParasiteSamplesCreateSelectionsController::class, 'humanSamples'])
        ->name('parasites.create.samples.human')
        ->middleware($authProjectWrite);
    Route::get('/create/samples/human/search', [ParasiteSamplesCreateSelectionsController::class, 'humanSamplesSearch'])
        ->name('parasites.create.samples.human.search')
        ->middleware($authProjectWrite);
    Route::get('/create/samples/animal', [ParasiteSamplesCreateSelectionsController::class, 'animalSamples'])
        ->name('parasites.create.samples.animal')
        ->middleware($authProjectWrite);
    Route::get('/create/samples/animal/search', [ParasiteSamplesCreateSelectionsController::class, 'animalSamplesSearch'])
        ->name('parasites.create.samples.animal.search')
        ->middleware($authProjectWrite);
    Route::get('/create/samples/environment', [ParasiteSamplesCreateSelectionsController::class, 'environmentSamples'])
        ->name('parasites.create.samples.environment')
        ->middleware($authProjectWrite);
    Route::get('/create/samples/environment/search', [ParasiteSamplesCreateSelectionsController::class, 'environmentSamplesSearch'])
        ->name('parasites.create.samples.environment.search')
        ->middleware($authProjectWrite);
    Route::get('/{code}', ParasiteSampleProfile::class)->middleware($authProject);
});

// Parasite Species
Route::prefix('parasites/species')->group(function () use ($authProjectWrite) {
    Route::get('/create', [ParasiteSpeciesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [ParasiteSpeciesController::class, 'store'])->name('parasites.species.store')->middleware($authProjectWrite);
    Route::post('/check-duplicate', [ParasiteSpeciesController::class, 'checkDuplicate'])
        ->name('parasites.species.check-duplicate')
        ->middleware($authProjectWrite);
});

// Nucleic acids
Route::prefix('samples/nucleic')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.nucleic_acids.index')->name('nucleic.index')->middleware($requireProjectSelection);
    Route::get('/create', [NucleicAcidsController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [NucleicAcidsController::class, 'store'])->name('nucleic.store')->middleware($authProjectWrite);

    Route::prefix('create')->group(function () use ($authProjectWrite) {
        Route::get('/tubes/human', [NucleicAcidsCreateSelectionsController::class, 'humanTubes'])
            ->name('nucleic.create.tubes.human')
            ->middleware($authProjectWrite);
        Route::get('/tubes/human/search', [NucleicAcidsCreateSelectionsController::class, 'humanTubesSearch'])
            ->name('nucleic.create.tubes.human.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/animal', [NucleicAcidsCreateSelectionsController::class, 'animalTubes'])
            ->name('nucleic.create.tubes.animal')
            ->middleware($authProjectWrite);
        Route::get('/tubes/animal/search', [NucleicAcidsCreateSelectionsController::class, 'animalTubesSearch'])
            ->name('nucleic.create.tubes.animal.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/environment', [NucleicAcidsCreateSelectionsController::class, 'environmentTubes'])
            ->name('nucleic.create.tubes.environment')
            ->middleware($authProjectWrite);
        Route::get('/tubes/environment/search', [NucleicAcidsCreateSelectionsController::class, 'environmentTubesSearch'])
            ->name('nucleic.create.tubes.environment.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/parasite', [NucleicAcidsCreateSelectionsController::class, 'parasiteTubes'])
            ->name('nucleic.create.tubes.parasite')
            ->middleware($authProjectWrite);
        Route::get('/tubes/parasite/search', [NucleicAcidsCreateSelectionsController::class, 'parasiteTubesSearch'])
            ->name('nucleic.create.tubes.parasite.search')
            ->middleware($authProjectWrite);

        Route::get('/experiments', [NucleicAcidsCreateSelectionsController::class, 'experiments'])
            ->name('nucleic.create.experiments')
            ->middleware($authProjectWrite);
        Route::get('/experiments/search', [NucleicAcidsCreateSelectionsController::class, 'experimentsSearch'])
            ->name('nucleic.create.experiments.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/culture', [NucleicAcidsCreateSelectionsController::class, 'cultureTubes'])
            ->name('nucleic.create.tubes.culture')
            ->middleware($authProjectWrite);
        Route::get('/tubes/culture/search', [NucleicAcidsCreateSelectionsController::class, 'cultureTubesSearch'])
            ->name('nucleic.create.tubes.culture.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/pool', [NucleicAcidsCreateSelectionsController::class, 'poolTubes'])
            ->name('nucleic.create.tubes.pool')
            ->middleware($authProjectWrite);
        Route::get('/tubes/pool/search', [NucleicAcidsCreateSelectionsController::class, 'poolTubesSearch'])
            ->name('nucleic.create.tubes.pool.search')
            ->middleware($authProjectWrite);
    });

    Route::get('/list', NucleicAcidsIndex::class)->middleware($authProject);
    Route::get('/dashboard', NucleicAcidsDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', NucleicAcidsDashboardMapPointsController::class)
        ->name('nucleic.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/all', [NucleicAcidsDashboardModalTablesController::class, 'all'])
        ->name('nucleic.dashboard.modal.all')
        ->middleware($authProject);
    Route::get('/dashboard/modal/human', [NucleicAcidsDashboardModalTablesController::class, 'human'])
        ->name('nucleic.dashboard.modal.human')
        ->middleware($authProject);
    Route::get('/dashboard/modal/animal', [NucleicAcidsDashboardModalTablesController::class, 'animal'])
        ->name('nucleic.dashboard.modal.animal')
        ->middleware($authProject);
    Route::get('/dashboard/modal/environment', [NucleicAcidsDashboardModalTablesController::class, 'environment'])
        ->name('nucleic.dashboard.modal.environment')
        ->middleware($authProject);
    Route::get('/dashboard/modal/parasite', [NucleicAcidsDashboardModalTablesController::class, 'parasite'])
        ->name('nucleic.dashboard.modal.parasite')
        ->middleware($authProject);
    Route::get('/dashboard/modal/culture', [NucleicAcidsDashboardModalTablesController::class, 'culture'])
        ->name('nucleic.dashboard.modal.culture')
        ->middleware($authProject);
    Route::get('/dashboard/modal/pool', [NucleicAcidsDashboardModalTablesController::class, 'pool'])
        ->name('nucleic.dashboard.modal.pool')
        ->middleware($authProject);
});

// Sequences routes - placed before the catch-all route
Route::prefix('samples/nucleic/sequences')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.nucleic_acids.sequences.index')->name('sequence.index')->middleware($requireProjectSelection);
    Route::get('/create', [SequencesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [SequencesController::class, 'store'])->name('sequences.store')->middleware($authProjectWrite);
    Route::get('/list', SequencesIndex::class)->middleware($authProject);
    Route::get('/dashboard', SequencesDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', SequencesDashboardMapPointsController::class)
        ->name('sequences.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/dashboard/modal/all', [SequencesDashboardModalTablesController::class, 'all'])
        ->name('sequences.dashboard.modal.all')
        ->middleware($authProject);
    Route::get('/dashboard/modal/human', [SequencesDashboardModalTablesController::class, 'human'])
        ->name('sequences.dashboard.modal.human')
        ->middleware($authProject);
    Route::get('/dashboard/modal/animal', [SequencesDashboardModalTablesController::class, 'animal'])
        ->name('sequences.dashboard.modal.animal')
        ->middleware($authProject);
    Route::get('/dashboard/modal/environment', [SequencesDashboardModalTablesController::class, 'environment'])
        ->name('sequences.dashboard.modal.environment')
        ->middleware($authProject);
    Route::get('/dashboard/modal/culture', [SequencesDashboardModalTablesController::class, 'culture'])
        ->name('sequences.dashboard.modal.culture')
        ->middleware($authProject);
    Route::get('/dashboard/modal/pool', [SequencesDashboardModalTablesController::class, 'pool'])
        ->name('sequences.dashboard.modal.pool')
        ->middleware($authProject);

    Route::prefix('create')->group(function () use ($authProjectWrite) {
        Route::get('/tubes/nucleic', [SequencesCreateSelectionsController::class, 'nucleicTubes'])
            ->name('sequences.create.nucleic_tubes')
            ->middleware($authProjectWrite);
        Route::get('/tubes/nucleic/search', [SequencesCreateSelectionsController::class, 'nucleicTubesSearch'])
            ->name('sequences.create.nucleic_tubes.search')
            ->middleware($authProjectWrite);
    });
});

// Catch-all route for nucleic acid profiles - placed last
Route::get('/samples/nucleic/{code}', NucleicAcidProfile::class)->middleware($authProject);
Route::get('/samples/nucleic/sequences/{code}', SequenceProfile::class)->middleware($authProject);
Route::post('/samples/nucleic/sequences/{code}/file', [SequencesController::class, 'uploadFile'])
    ->name('sequences.file.upload')
    ->middleware($authProjectWrite);
Route::delete('/samples/nucleic/sequences/{code}/file', [SequencesController::class, 'deleteFile'])
    ->name('sequences.file.delete')
    ->middleware($authProjectWrite);

// Cultures
Route::prefix('samples/cultures')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'samples.cultures.index')->name('cultures.index')->middleware($requireProjectSelection);
    Route::get('/create', [CulturesController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [CulturesController::class, 'store'])->name('cultures.store')->middleware($authProjectWrite);
    Route::get('/list', CulturesIndex::class)->middleware($authProject);
    Route::get('/dashboard', CulturesDashboard::class)->middleware($authProject);

    Route::prefix('create')->group(function () use ($authProjectWrite) {
        Route::get('/tubes/human', [CulturesCreateSelectionsController::class, 'humanTubes'])
            ->name('cultures.create.tubes.human')
            ->middleware($authProjectWrite);
        Route::get('/tubes/human/search', [CulturesCreateSelectionsController::class, 'humanTubesSearch'])
            ->name('cultures.create.tubes.human.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/animal', [CulturesCreateSelectionsController::class, 'animalTubes'])
            ->name('cultures.create.tubes.animal')
            ->middleware($authProjectWrite);
        Route::get('/tubes/animal/search', [CulturesCreateSelectionsController::class, 'animalTubesSearch'])
            ->name('cultures.create.tubes.animal.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/environment', [CulturesCreateSelectionsController::class, 'environmentTubes'])
            ->name('cultures.create.tubes.environment')
            ->middleware($authProjectWrite);
        Route::get('/tubes/environment/search', [CulturesCreateSelectionsController::class, 'environmentTubesSearch'])
            ->name('cultures.create.tubes.environment.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/parasite', [CulturesCreateSelectionsController::class, 'parasiteTubes'])
            ->name('cultures.create.tubes.parasite')
            ->middleware($authProjectWrite);
        Route::get('/tubes/parasite/search', [CulturesCreateSelectionsController::class, 'parasiteTubesSearch'])
            ->name('cultures.create.tubes.parasite.search')
            ->middleware($authProjectWrite);

        Route::get('/tubes/pool', [CulturesCreateSelectionsController::class, 'poolTubes'])
            ->name('cultures.create.tubes.pool')
            ->middleware($authProjectWrite);
        Route::get('/tubes/pool/search', [CulturesCreateSelectionsController::class, 'poolTubesSearch'])
            ->name('cultures.create.tubes.pool.search')
            ->middleware($authProjectWrite);

        Route::get('/cultures', [CulturesCreateSelectionsController::class, 'cultures'])
            ->name('cultures.create.cultures')
            ->middleware($authProjectWrite);
        Route::get('/cultures/search', [CulturesCreateSelectionsController::class, 'culturesSearch'])
            ->name('cultures.create.cultures.search')
            ->middleware($authProjectWrite);
    });

    Route::get('/{code}', CultureProfile::class)->middleware($authProject);
});

// Experiments
Route::prefix('experiments')->group(function () use ($authProject, $authProjectWrite, $requireProjectSelection) {
    Route::view('/', 'experiments.index')->name('experiments.index')->middleware($requireProjectSelection);

    Route::get('/create', [ExperimentsController::class, 'create'])->middleware($authProjectWrite);
    Route::prefix('create/tubes')->group(function () use ($authProjectWrite) {
        Route::get('/human', [ExperimentsCreateTubesController::class, 'human'])
            ->name('experiments.create.tubes.human')
            ->middleware($authProjectWrite);
        Route::get('/animal', [ExperimentsCreateTubesController::class, 'animal'])
            ->name('experiments.create.tubes.animal')
            ->middleware($authProjectWrite);
        Route::get('/environment', [ExperimentsCreateTubesController::class, 'environment'])
            ->name('experiments.create.tubes.environment')
            ->middleware($authProjectWrite);
        Route::get('/parasite', [ExperimentsCreateTubesController::class, 'parasite'])
            ->name('experiments.create.tubes.parasite')
            ->middleware($authProjectWrite);
        Route::get('/nucleic', [ExperimentsCreateTubesController::class, 'nucleic'])
            ->name('experiments.create.tubes.nucleic')
            ->middleware($authProjectWrite);
        Route::get('/culture', [ExperimentsCreateTubesController::class, 'culture'])
            ->name('experiments.create.tubes.culture')
            ->middleware($authProjectWrite);
        Route::get('/pool', [ExperimentsCreateTubesController::class, 'pool'])
            ->name('experiments.create.tubes.pool')
            ->middleware($authProjectWrite);

        Route::get('/human/search', [ExperimentsCreateTubesController::class, 'humanSearch'])
            ->name('experiments.create.tubes.human.search')
            ->middleware($authProjectWrite);
        Route::get('/animal/search', [ExperimentsCreateTubesController::class, 'animalSearch'])
            ->name('experiments.create.tubes.animal.search')
            ->middleware($authProjectWrite);
        Route::get('/environment/search', [ExperimentsCreateTubesController::class, 'environmentSearch'])
            ->name('experiments.create.tubes.environment.search')
            ->middleware($authProjectWrite);
        Route::get('/parasite/search', [ExperimentsCreateTubesController::class, 'parasiteSearch'])
            ->name('experiments.create.tubes.parasite.search')
            ->middleware($authProjectWrite);
        Route::get('/nucleic/search', [ExperimentsCreateTubesController::class, 'nucleicSearch'])
            ->name('experiments.create.tubes.nucleic.search')
            ->middleware($authProjectWrite);
        Route::get('/culture/search', [ExperimentsCreateTubesController::class, 'cultureSearch'])
            ->name('experiments.create.tubes.culture.search')
            ->middleware($authProjectWrite);
        Route::get('/pool/search', [ExperimentsCreateTubesController::class, 'poolSearch'])
            ->name('experiments.create.tubes.pool.search')
            ->middleware($authProjectWrite);
    });

    Route::post('/', [ExperimentsController::class, 'store'])->name('experiments.store')->middleware($authProjectWrite);
    Route::post('/suitability', [ExperimentsController::class, 'suitability'])->name('experiments.suitability')->middleware($authProjectWrite);

    Route::get('/list', ExperimentsIndex::class)->name('experiments.list')->middleware($authProject);
    Route::get('/dashboard', ExperimentsDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', ExperimentsDashboardMapPointsController::class)
        ->name('experiments.dashboard.map-points')
        ->middleware($authProject);

    Route::get('/statistics', ExperimentsStatistics::class)->middleware($authProject);
    Route::get('/statistics/overview', ExperimentsStatisticsOverview::class)->middleware($authProject);
    Route::get('/statistics/detailed', ExperimentsStatisticsDetailed::class)->middleware($authProject);

    Route::get('/{code}', ExperimentProfile::class)->middleware(['auth']);
});

Route::get('/protocols/{code}', ProtocolProfile::class)->middleware(['auth']);

// Experiments dependencies
Route::middleware($authProjectWrite)->group(function () {
    Route::post('/protocols', [ProtocolsController::class, 'store'])->name('protocols.store');
    Route::post('/nucleic_protocols', [NucleicProtocolsController::class, 'store'])->name('nucleic_protocols.store');
    Route::post('/pathogens', [PathogensController::class, 'store'])->name('pathogens.store');
    Route::post('/pathogens_protocols', [PathogensProtocolsController::class, 'store'])->name('pathogens_protocols.store');
    Route::post('/pathogens_protocols/detach', [PathogensProtocolsController::class, 'detach']);
    Route::post('/studies', [StudiesController::class, 'store'])->name('studies.store');
});

// Sampling sites
Route::get('/sampling_sites/create', [SamplingSitesController::class, 'create'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/sampling_sites', [SamplingSitesController::class, 'store'])->name('sites.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Laboratories
Route::get('/laboratories/create', [LaboratoriesController::class, 'create'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/laboratories', [LaboratoriesController::class, 'store'])->name('laboratories.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Locations
Route::get('/locations/create', [LocationsController::class, 'create'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/locations', [LocationsController::class, 'store'])->name('locations.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Organizations
Route::get('/organizations/create', [OrganizationsController::class, 'create'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/organizations', [OrganizationsController::class, 'store'])->name('organizations.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Departments
Route::get('/departments/create', [DepartmentsController::class, 'create'])->name('departments.create')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/departments', [DepartmentsController::class, 'store'])->name('departments.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

// Storage
Route::view('/bank', 'bank.index')->name('bank.index')->middleware(RequireProjectSelection::class);

Route::view('/bank/tubes', 'bank/tubes.index')->middleware(RequireProjectSelection::class);
Route::get('/bank/tubes/create', [TubesController::class, 'create_positions'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/bank/tubes', [TubesController::class, 'store_positions'])->name('tube_positions.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::get('/bank/tubes/dashboard', TubePositionsDashboard::class)->middleware(['auth', RequireProjectSelection::class]);
Route::get('/bank/tubes/dashboard/map-points', TubePositionsDashboardMapPointsController::class)
    ->name('tube-positions.dashboard.map-points')
    ->middleware(['auth', RequireProjectSelection::class]);
Route::get('/bank/tubes/list', TubePositionsIndex::class)->middleware(['auth', RequireProjectSelection::class]);
Route::get('/bank/tubes/{code}', TubeProfile::class)->middleware(['auth', RequireProjectSelection::class]);

Route::prefix('/bank/tubes/create/tubes')->group(function () use ($namedGet, $authProjectWrite) {
    $routes = [
        'human' => ['humanTubes', 'humanTubesSearch'],
        'animal' => ['animalTubes', 'animalTubesSearch'],
        'environment' => ['environmentTubes', 'environmentTubesSearch'],
        'parasite' => ['parasiteTubes', 'parasiteTubesSearch'],
        'nucleic' => ['nucleicTubes', 'nucleicTubesSearch'],
        'culture' => ['cultureTubes', 'cultureTubesSearch'],
        'pool' => ['poolTubes', 'poolTubesSearch'],
    ];

    foreach ($routes as $segment => [$listMethod, $searchMethod]) {
        $namedGet(
            "/{$segment}",
            [TubePositionsCreateSelectionsController::class, $listMethod],
            "bank.tubes.create.tubes.{$segment}",
            $authProjectWrite
        );
        $namedGet(
            "/{$segment}/search",
            [TubePositionsCreateSelectionsController::class, $searchMethod],
            "bank.tubes.create.tubes.{$segment}.search",
            $authProjectWrite
        );
    }
});

Route::get('/bank/boxes/{box}/latest-tube-positions', [TubesController::class, 'latestBoxTubePositions'])
    ->middleware(['auth', RequireProjectSelection::class]);

Route::view('/bank/boxes', 'bank/boxes.index')->middleware(RequireProjectSelection::class);
Route::get('/bank/boxes/create', [BoxesController::class, 'create'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/bank/boxes', [BoxesController::class, 'store_boxes'])->name('boxes.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::get('/bank/boxes/list', BoxPositionsList::class)->middleware(['auth', RequireProjectSelection::class]);
Route::post('/bank/boxes/positions', [BoxesController::class, 'store_positions'])->name('boxes.positions.store')->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::get('/bank/boxes/{boxId}/contents', BoxContents::class)->middleware(['auth', RequireProjectSelection::class]);

// Meta routes
Route::prefix('/meta')->group(function () use ($authProject, $authProjectWrite) {
    Route::view('/', 'meta.index')->middleware($authProject);

    Route::get('/create', [MetaController::class, 'create'])
        ->name('meta.create')
        ->middleware($authProjectWrite);
    Route::post('/', [MetaController::class, 'store'])
        ->name('meta.store')
        ->middleware($authProjectWrite);

    Route::get('/create/studies', [MetaCreateSelectionsController::class, 'studies'])
        ->name('meta.create.studies')
        ->middleware($authProjectWrite);
    Route::get('/create/studies/search', [MetaCreateSelectionsController::class, 'studiesSearch'])
        ->name('meta.create.studies.search')
        ->middleware($authProjectWrite);

    $lists = [
        'animal' => MetaAnimalIndex::class,
        'human' => MetaHumanIndex::class,
        'environment' => MetaEnvironmentIndex::class,
        'parasite' => MetaParasiteIndex::class,
    ];

    foreach ($lists as $segment => $component) {
        Route::get("/list/{$segment}", $component)->middleware($authProject);
    }

    Route::get('/dashboard', MetaDashboard::class)->middleware($authProject);

    Route::get('/profile/{code}', function ($code) {
        return view('meta.profile', ['code' => $code]);
    })->name('meta.profile')->middleware($authProject);
});

Route::get('/studies/{id}', StudyProfile::class)->middleware(['auth']);

Route::prefix('/team')->group(function () use ($authProject, $authProjectWrite) {
    Route::get('/', [TeamController::class, 'index'])->middleware($authProject);
    Route::post('/', [TeamController::class, 'store'])->name('team.store')->middleware($authProjectWrite);
    Route::post('/check-email', [TeamController::class, 'checkEmail'])->name('team.check-email')->middleware('auth');

    $mutations = [
        'update-role' => ['updateRole', 'team.updateRole'],
        'update-permission' => ['updatePermission', 'team.updatePermission'],
        'update-module-permissions' => ['updateModulePermissions', 'team.updateModulePermissions'],
        'update-sub-projects' => ['updateSubProjects', 'team.updateSubProjects'],
        'update-date-joined' => ['updateDateJoined', 'team.updateDateJoined'],
        'detach' => ['detach', 'team.detach'],
    ];

    foreach ($mutations as $segment => [$method, $name]) {
        Route::post("/{person}/{$segment}", [TeamController::class, $method])->name($name)->middleware($authProjectWrite);
    }
});

Route::prefix('/documents')->group(function () use ($authProject, $authProjectWrite) {
    Route::get('/', [DocumentsController::class, 'index'])->middleware($authProject);
    Route::post('/', [DocumentsController::class, 'store'])->name('documents.store')->middleware($authProjectWrite);
    Route::patch('/{document}', [DocumentsController::class, 'update'])->name('documents.update')->middleware($authProject);
    Route::delete('/{document}', [DocumentsController::class, 'destroy'])->name('documents.destroy')->middleware($authProject);
});

// Authentication routes
Route::get('/register', [RegisteredUserController::class, 'create']);
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/register/validation/organization-name', [NameValidationController::class, 'checkOrganizationForRegistration'])
    ->name('register.validation.organization-name');

// Route::get('/register/invitation', [App\Http\Controllers\RegisteredUserController::class, 'invitationView'])->name('register.invitation');
// Route::post('/register/invitation/{invitation}', [App\Http\Controllers\RegisteredUserController::class, 'handleInvitation'])->name('register.invitation.handle');
// Route::get('/profile/invitations', [App\Http\Controllers\ProfileController::class, 'invitations'])->name('profile.invitations');

// Email verification routes
Route::get('/verify-email', [RegisteredUserController::class, 'showVerificationForm'])->name('verify-email');
Route::post('/verify-email', [RegisteredUserController::class, 'verifyEmail'])->name('verify-email.post');
Route::post('/resend-verification', [RegisteredUserController::class, 'resendVerification'])->name('resend-verification');

Route::get('/login', [SessionController::class, 'create'])->name('login');
Route::post('/login', [SessionController::class, 'store']);
Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

// Two-factor challenge shown at login (skippable during the grace window)
Route::middleware('auth')->group(function () {
    Route::get('/login/2fa', [TwoFactorController::class, 'prompt'])->name('two-factor.prompt');
    Route::post('/login/2fa', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/login/2fa/postpone', [TwoFactorController::class, 'postpone'])->name('two-factor.postpone');
});

// Passkey (WebAuthn) authentication
Route::post('/webauthn/login/options', [WebAuthnLoginController::class, 'options'])->name('webauthn.login.options');
Route::post('/webauthn/login', [WebAuthnLoginController::class, 'login'])->name('webauthn.login');
Route::middleware('auth')->group(function () {
    Route::post('/webauthn/register/options', [WebAuthnRegisterController::class, 'options'])->name('webauthn.register.options');
    Route::post('/webauthn/register', [WebAuthnRegisterController::class, 'register'])->name('webauthn.register');
    Route::delete('/webauthn/passkeys/{credential}', [PasskeyController::class, 'destroy'])->name('webauthn.passkeys.destroy');
});

// Authenticated file serving — replaces the framework's public storage route so
// no stored file (sequences, photos, documents) is reachable without a session.
Route::get('/storage/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('storage.local');

// Password reset routes (email is the safe key)
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.store');

// Announcements (global, visible in guest mode too)
Route::get('/announcements', [AnnouncementsController::class, 'index'])->name('announcements.index');
Route::post('/announcements/mark-all-read', [AnnouncementsController::class, 'markAllRead'])
    ->middleware('auth')
    ->name('announcements.mark-all-read');

// Admin (global announcements)
Route::prefix('admin')->middleware(['auth', 'require.admin'])->group(function () {
    Route::get('/announcements', [AnnouncementsAdminController::class, 'index'])->name('admin.announcements.index');
    Route::get('/announcements/create', [AnnouncementsAdminController::class, 'create'])->name('admin.announcements.create');
    Route::post('/announcements', [AnnouncementsAdminController::class, 'store'])->name('admin.announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementsAdminController::class, 'edit'])->name('admin.announcements.edit');
    Route::patch('/announcements/{announcement}', [AnnouncementsAdminController::class, 'update'])->name('admin.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementsAdminController::class, 'destroy'])->name('admin.announcements.destroy');

    Route::get('/lookups', [GlobalLookupAdminController::class, 'index'])->name('admin.lookups.index');
    Route::get('/lookups/{lookup}', [GlobalLookupAdminController::class, 'show'])->name('admin.lookups.show');
    Route::get('/lookups/{lookup}/create', [GlobalLookupAdminController::class, 'create'])->name('admin.lookups.create');
    Route::post('/lookups/{lookup}', [GlobalLookupAdminController::class, 'store'])->name('admin.lookups.store');
    Route::get('/lookups/{lookup}/{id}/edit', [GlobalLookupAdminController::class, 'edit'])->name('admin.lookups.edit');
    Route::patch('/lookups/{lookup}/{id}', [GlobalLookupAdminController::class, 'update'])->name('admin.lookups.update');
    Route::delete('/lookups/{lookup}/{id}', [GlobalLookupAdminController::class, 'destroy'])->name('admin.lookups.destroy');

    Route::get('/publication-reviews', [PublicationReviewAdminController::class, 'index'])->name('admin.publication-reviews.index');
    Route::get('/publication-reviews/{publicationReviewRequest}', [PublicationReviewAdminController::class, 'show'])->name('admin.publication-reviews.show');
    Route::post('/publication-reviews/{publicationReviewRequest}/decide', [PublicationReviewAdminController::class, 'decide'])->name('admin.publication-reviews.decide');
});

Route::middleware(['auth'])->group(function () {
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead']);

    // Profile routes
    Route::get('/profile/{id}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile')->middleware(['verified']);
    Route::get('/my-projects', MyProjects::class)->name('profile.projects');
    Route::post('/projects/check-team-email', [TeamController::class, 'checkEmail'])->name('projects.check-team-email');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::post('/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');

    // Two-factor authentication (authenticator app) enrolment
    Route::post('/settings/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/settings/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::post('/settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/profile/upload-photo', [ProfileController::class, 'uploadPhoto'])->name('profile.upload-photo');
    Route::delete('/profile/delete-photo', [ProfileController::class, 'deletePhoto'])->name('profile.delete-photo');
    Route::patch('/profile/update-field', [ProfileController::class, 'updateField'])->name('profile.update-field');
    // Allow any authenticated user (including project viewers) to create an organization
    // while editing their personal profile.
    Route::post('/profile/organizations', [OrganizationsController::class, 'store'])->name('profile.organizations.store');

    // Chat routes
    Route::get('/chat', [MessagesController::class, 'index'])->name('chat.index')->middleware(RequireProjectSelection::class);
    Route::get('/messages/unread/count', [MessagesController::class, 'getUnreadCount'])->name('messages.unread.count')->middleware(RequireProjectSelection::class);
    Route::get('/messages/online-statuses', [MessagesController::class, 'onlineStatuses'])->name('messages.online-statuses')->middleware(RequireProjectSelection::class);
    Route::get('/messages/{userId}/unread/count', [MessagesController::class, 'getUserUnreadCount'])->name('messages.user.unread.count')->middleware(RequireProjectSelection::class);
    Route::post('/messages/typing/start', [MessagesController::class, 'typingStart'])->name('messages.typing.start')->middleware(RequireProjectSelection::class);
    Route::post('/messages/typing/stop', [MessagesController::class, 'typingStop'])->name('messages.typing.stop')->middleware(RequireProjectSelection::class);
    Route::post('/messages/heartbeat', [MessagesController::class, 'heartbeat'])->name('messages.heartbeat')->middleware(RequireProjectSelection::class);
    Route::get('/messages/{userId}/typing/status', [MessagesController::class, 'typingStatus'])->name('messages.typing.status')->middleware(RequireProjectSelection::class);
    Route::get('/messages/{userId}', [MessagesController::class, 'getMessages'])->name('messages.get')->middleware(RequireProjectSelection::class);
    Route::post('/messages', [MessagesController::class, 'sendMessage'])->name('messages.send')->middleware(RequireProjectSelection::class);

    // Project review only — the remaining project, funding and sub-project
    // routes live in the dedicated auth+verified group further down.
    Route::get('/projects/review', [ProjectsController::class, 'review'])->name('projects.review');

    // Guest mode routes (no project selection required)
    $guestRoutes = [
        ['/guest/experiments', ExperimentsIndex::class, 'guest.experiments'],
        ['/guest/animal-samples', AnimalSamplesIndex::class, 'guest.animal-samples'],
        ['/guest/human-samples', HumanSamplesIndex::class, 'guest.human-samples'],
        ['/guest/parasite-samples', ParasiteSamplesIndex::class, 'guest.parasite-samples'],
        ['/guest/nucleic-acids', NucleicAcidsIndex::class, 'guest.nucleic-acids'],
        ['/guest/sequences', SequencesIndex::class, 'guest.sequences'],
        ['/guest/cultures', CulturesIndex::class, 'guest.cultures'],
        ['/guest/pools', PoolsIndex::class, 'guest.pools'],
        ['/guest/parasites', ParasitesIndex::class, 'guest.parasites'],
        ['/guest/meta/animal', MetaAnimalIndex::class, 'guest.meta.animal'],
        ['/guest/meta/human', MetaHumanIndex::class, 'guest.meta.human'],
        ['/guest/meta/environment', MetaEnvironmentIndex::class, 'guest.meta.environment'],
        ['/guest/meta/parasite', MetaParasiteIndex::class, 'guest.meta.parasite'],
    ];

    foreach ($guestRoutes as [$uri, $component, $name]) {
        Route::get($uri, $component)->name($name);
    }

    // Tube requests routes
    Route::get('/tube-requests', TubeRequestsManager::class)->name('tube-requests');

    // Guest mode routes for specialized experiment indexes
    foreach (['human', 'animal', 'environment', 'parasite', 'culture', 'pool', 'nucleic'] as $type) {
        Route::get("/guest/experiments/{$type}", ExperimentsIndex::class)->name("guest.experiments.{$type}");
    }

    // Guest mode routes for specialized sample indexes
    foreach (['human', 'animal', 'environment'] as $type) {
        Route::get("/guest/parasite-samples/{$type}", ParasiteSamplesIndex::class)->name("guest.parasite-samples.{$type}");
    }

    // Guest mode routes for specialized nucleic acid indexes
    foreach (['human', 'animal', 'environment', 'parasite', 'culture', 'pool'] as $type) {
        Route::get("/guest/nucleic-acids/{$type}", NucleicAcidsIndex::class)->name("guest.nucleic-acids.{$type}");
    }

    // Guest mode routes for specialized culture indexes
    foreach (['human', 'animal', 'environment', 'parasite'] as $type) {
        Route::get("/guest/cultures/{$type}", CulturesIndex::class)->name("guest.cultures.{$type}");
    }

    foreach (['human', 'animal', 'environment'] as $type) {
        Route::get("/guest/cultures/parasite/{$type}", CulturesIndex::class)->name("guest.cultures.parasite.{$type}");
    }

    Route::get('/guest/cultures/pool', CulturesIndex::class)->name('guest.cultures.pool');
    foreach (['human', 'animal', 'environment', 'parasite'] as $type) {
        Route::get("/guest/cultures/pool/{$type}", CulturesIndex::class)->name("guest.cultures.pool.{$type}");
    }

    // Guest mode routes for specialized sequence views (single unified index)
    foreach (['human', 'animal', 'environment', 'parasite', 'culture', 'pool'] as $type) {
        Route::get("/guest/sequences/{$type}", SequencesIndex::class)->name("guest.sequences.{$type}");
    }
    Route::get('/guest/sequences/profile/{code}', SequenceProfile::class)->name('guest.sequences.profile');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.current');
    Route::patch('/profile', [ProfileController::class, 'updateSettings'])->name('profile.update');
    Route::get('/projects/create', [ProjectsController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectsController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit/{section?}', [ProjectsController::class, 'edit'])->name('projects.edit')->middleware(['require.project.write']);
    Route::post('/projects/{project}/complete', [ProjectsController::class, 'markComplete'])->name('projects.complete')->middleware(['require.project.write']);
    Route::get('/projects/{code}', ProjectProfile::class)->name('projects.profile');
    Route::get('/fundings/{funding}', FundingProfile::class)->name('fundings.profile');
    Route::patch('/projects/{project}/{section?}', [ProjectsController::class, 'update'])->name('projects.update')->middleware(['require.project.write']);
    Route::delete('/projects/{project}', [ProjectsController::class, 'destroy'])
        ->name('projects.destroy');
    Route::post('/project/select', [ProjectSelectionController::class, 'update'])->name('project.select');
    Route::delete('/projects/{project}/funding/{funding}', [ProjectsController::class, 'detachFunding'])
        ->name('projects.funding.detach')
        ->middleware(['require.project.write']);
    Route::delete('/projects/{project}/document/{document}', [ProjectsController::class, 'detachDocument'])
        ->name('projects.document.detach')
        ->middleware(['require.project.write']);
    Route::post('/sub-projects', [SubProjectsController::class, 'store'])
        ->name('sub-projects.store')
        ->middleware(['require.project.write']);
    Route::patch('/sub-projects/{subProject}', [SubProjectsController::class, 'update'])
        ->name('sub-projects.update')
        ->middleware(['require.project.write']);
    Route::post('/sub-projects/{subProject}/complete', [SubProjectsController::class, 'markComplete'])
        ->name('sub-projects.complete')
        ->middleware(['require.project.write']);
    Route::post('/sub-projects/check-code', [SubProjectsController::class, 'checkCode'])
        ->name('sub-projects.check-code')
        ->middleware(['require.project.write']);
    Route::delete('/sub-projects/{subProject}', [SubProjectsController::class, 'destroy'])
        ->name('sub-projects.destroy')
        ->middleware(['require.project.write']);
});

Route::get('/check-email-matches', [RegisteredUserController::class, 'checkEmailMatches'])->name('check-email-matches');
Route::get('/check-orcid', [RegisteredUserController::class, 'checkOrcid'])->name('check-orcid');

// Pools routes
Route::prefix('/samples/pools')->group(function () use ($authProject, $authProjectWrite) {
    Route::view('/', 'samples.pools.index')->name('pools.index')->middleware($authProject);
    Route::get('/create', [PoolsController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [PoolsController::class, 'store'])->name('pools.store')->middleware($authProjectWrite);
    Route::get('/list', PoolsIndex::class)->middleware($authProject);
    Route::get('/dashboard', PoolsDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', PoolsDashboardMapPointsController::class)
        ->name('pools.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/{code}', PoolProfile::class)->middleware($authProject);
});

Route::prefix('/samples/microplastics')->group(function () use ($authProject, $authProjectWrite) {
    Route::view('/', 'samples.microplastics.index')->name('microplastics.index')->middleware($authProject);
    Route::get('/create', [MicroplasticsController::class, 'create'])->middleware($authProjectWrite);
    Route::post('/', [MicroplasticsController::class, 'store'])->name('microplastics.store')->middleware($authProjectWrite);
    Route::get('/list', MicroplasticsIndex::class)->middleware($authProject);
    Route::get('/dashboard', MicroplasticsDashboard::class)->middleware($authProject);
    Route::get('/dashboard/map-points', MicroplasticsDashboardMapPointsController::class)
        ->name('microplastics.dashboard.map-points')
        ->middleware($authProject);
    Route::get('/{code}', MicroplasticProfile::class)->middleware($authProject);
});

Route::prefix('/samples/microplastics/create/tubes')->group(function () use ($namedGet, $authProjectWrite) {
    $routes = [
        'human' => ['humanTubes', 'humanTubesSearch'],
        'animal' => ['animalTubes', 'animalTubesSearch'],
        'environment' => ['environmentTubes', 'environmentTubesSearch'],
        'parasite' => ['parasiteTubes', 'parasiteTubesSearch'],
        'nucleic' => ['nucleicTubes', 'nucleicTubesSearch'],
        'culture' => ['cultureTubes', 'cultureTubesSearch'],
        'pool' => ['poolTubes', 'poolTubesSearch'],
    ];

    foreach ($routes as $segment => [$listMethod, $searchMethod]) {
        $namedGet(
            "/{$segment}",
            [MicroplasticsCreateSelectionsController::class, $listMethod],
            "microplastics.create.tubes.{$segment}",
            $authProjectWrite
        );
        $namedGet(
            "/{$segment}/search",
            [MicroplasticsCreateSelectionsController::class, $searchMethod],
            "microplastics.create.tubes.{$segment}.search",
            $authProjectWrite
        );
    }
});

Route::post('/microplastics_protocols', [MicroplasticsProtocolsController::class, 'store'])
    ->name('microplastics_protocols.store')
    ->middleware($authProjectWrite);

Route::prefix('/samples/pools/create/tubes')->group(function () use ($namedGet, $authProjectWrite) {
    $routes = [
        'human' => ['humanTubes', 'humanTubesSearch'],
        'animal' => ['animalTubes', 'animalTubesSearch'],
        'environment' => ['environmentTubes', 'environmentTubesSearch'],
        'parasite' => ['parasiteTubes', 'parasiteTubesSearch'],
        'nucleic' => ['nucleicTubes', 'nucleicTubesSearch'],
        'culture' => ['cultureTubes', 'cultureTubesSearch'],
    ];

    foreach ($routes as $segment => [$listMethod, $searchMethod]) {
        $namedGet(
            "/{$segment}",
            [PoolsCreateSelectionsController::class, $listMethod],
            "pools.create.tubes.{$segment}",
            $authProjectWrite
        );
        $namedGet(
            "/{$segment}/search",
            [PoolsCreateSelectionsController::class, $searchMethod],
            "pools.create.tubes.{$segment}.search",
            $authProjectWrite
        );
    }
});

// Animals
Route::get('/animals/{code}', AnimalProfile::class)->middleware(['auth', RequireProjectSelection::class]);

// Parasites
Route::get('/parasites/{code}', ParasiteProfile::class)->middleware(['auth', RequireProjectSelection::class]);

// Pathogens
Route::get('/pathogens/{code}', PathogenProfile::class)->middleware(['auth', RequireProjectSelection::class]);

// Field-collected samples processing
Route::get('/samples/process', [TubesController::class, 'create_field_processing'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);
Route::post('/samples/process', [TubesController::class, 'store_field_processing'])->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

Route::prefix('/samples/process/samples')->group(function () use ($namedGet, $authProjectWrite) {
    $routes = [
        'human' => ['human', 'humanSearch'],
        'animal' => ['animal', 'animalSearch'],
        'environment' => ['environment', 'environmentSearch'],
        'parasite' => ['parasite', 'parasiteSearch'],
        'nucleic' => ['nucleic', 'nucleicSearch'],
        'culture' => ['culture', 'cultureSearch'],
        'pool' => ['pool', 'poolSearch'],
    ];

    foreach ($routes as $segment => [$listMethod, $searchMethod]) {
        $namedGet(
            "/{$segment}",
            [FieldSamplesProcessSelectionsController::class, $listMethod],
            "samples.process.samples.{$segment}",
            $authProjectWrite
        );
        $namedGet(
            "/{$segment}/search",
            [FieldSamplesProcessSelectionsController::class, $searchMethod],
            "samples.process.samples.{$segment}.search",
            $authProjectWrite
        );
    }
});

// Tubes list
Route::get('/samples/process/list', TubesList::class)->middleware(['auth', RequireProjectSelection::class]);

// Publish data route
Route::get('/publish', PublishData::class)->middleware(['auth', RequireProjectSelection::class, 'require.project.write']);

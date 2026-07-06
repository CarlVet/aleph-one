<?php

namespace App\Livewire\Forms;

use App\Enums\ExperimentPurpose;
use App\Models\Experiments;
use App\Models\Laboratories;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Protocols;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class ExperimentsForm extends Form
{
    use WithFileUploads;

    public $experiments;

    public $photo;

    public $projectId = 1;

    public function mount()
    {
        $this->experiments = Experiments::with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'projects',
        )->where('projects_id', $this->projectId);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function updateField($sampleId, $field, $value): array
    {
        $experiment = Experiments::find($sampleId);

        if (! $experiment) {
            return [
                'ok' => false,
                'message' => 'Unable to update: experiment not found.',
            ];
        }

        switch ($field) {
            case 'protocol':
                $protocol = Protocols::where('name', $value)->first();

                if (! $protocol) {
                    return ['ok' => false, 'message' => 'Protocol not found. Please select an existing protocol.'];
                }

                $experiment->update(['protocols_id' => $protocol->id]);
                break;
            case 'pathogen':
                $pathogen = Pathogens::where('species', $value)->first();

                if (! $pathogen) {
                    return ['ok' => false, 'message' => 'Pathogen not found. Please select an existing pathogen.'];
                }

                $experiment->update(['pathogens_id' => $pathogen->id]);
                break;
            case 'outcome_discrete':
                $experiment->update(['outcome_discrete' => $value]);
                break;
            case 'purpose':
                $purpose = ExperimentPurpose::tryFrom((string) $value);

                if (! $purpose) {
                    return ['ok' => false, 'message' => 'Purpose must be either screening or confirmation.'];
                }

                $experiment->update(['purpose' => $purpose]);
                break;
            case 'outcome_quant':
                $experiment->update(['outcome_quant' => $value]);
                break;
            case 'date_tested':
                $experiment->update(['date_tested' => $value]);
                break;
            case 'lab':
                $laboratory = Laboratories::where('name', $value)->first();

                if (! $laboratory) {
                    return ['ok' => false, 'message' => 'Laboratory not found. Please select an existing laboratory.'];
                }

                $experiment->update(['laboratories_id' => $laboratory->id]);
                break;
            case 'people_id':
                $personId = (int) $value;
                $person = People::find($personId);

                if (! $person) {
                    return ['ok' => false, 'message' => 'Registrar not found. Please select an existing person.'];
                }

                $experiment->update(['people_id' => $person->id]);
                break;
        }

        return [
            'ok' => true,
            'message' => 'Experiment updated successfully!',
        ];
    }

    public function refreshData()
    {
        $this->experiments = Experiments::with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'projects',
        )->where('projects_id', $this->projectId)
            ->get();
    }
}

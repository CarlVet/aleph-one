<?php

namespace App\Livewire\Forms;

use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\Protocols;
use App\Models\Tubes;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class NucleicAcidsForm extends Form
{
    use WithFileUploads;

    public $nucleic_tubes;

    public $photo;

    public function mount()
    {
        $this->nucleic_tubes = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', NucleicAcids::class);
        })->with(
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects'
        )->get();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function updateField($sampleId, $field, $value): array
    {
        $sample = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', NucleicAcids::class);
        })->find($sampleId);

        if (! $sample) {
            return [
                'ok' => false,
                'message' => 'Unable to update: tube not found.',
            ];
        }

        switch ($field) {
            case 'state':
                $sample->update(['preservant' => $value]);
                break;
            case 'nucleic_id':
                $sample->update(['tubes_content_id' => $value]);
                break;
            case 'nucleic_type':
                $sample->tubes_content()->update(['type' => $value]);
                break;
            case 'protocol':
                $protocol = Protocols::where('name', $value)->first();

                if (! $protocol) {
                    return [
                        'ok' => false,
                        'message' => 'Protocol not found. Please create it first.',
                    ];
                }

                $sample->tubes_content()->update(['protocols_id' => $protocol->id]);
                break;
            case 'date_extracted':
                $sample->tubes_content()->update(['date_extracted' => $value]);
                break;
            case 'volume':
                $sample->tubes_content()->update(['volume' => $value]);
                break;
            case 'extracted_at':
                $laboratory = Laboratories::where('name', $value)->first();

                if (! $laboratory) {
                    return [
                        'ok' => false,
                        'message' => 'Laboratory not found. Please select an existing laboratory.',
                    ];
                }

                $sample->tubes_content()->update(['laboratories_id' => $laboratory->id]);
                break;
        }

        $this->refreshData();

        return [
            'ok' => true,
            'message' => 'Nucleic tube updated successfully!',
        ];
    }

    public function refreshData()
    {
        $this->nucleic_tubes = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', NucleicAcids::class);
        })->with(
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects'
        )->get();
    }
}

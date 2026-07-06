<?php

namespace App\Livewire\Forms;

use App\Models\Sequences;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class SequencesForm extends Form
{
    use WithFileUploads;

    public $sequences;

    public $photo;

    public function mount()
    {
        $this->sequences = Sequences::with([
            'nucleic_acids',
            'people',
            'laboratories',
            'projects',
        ])->get();
    }

    public function updateField($sequenceId, $field, $value)
    {
        $sequence = Sequences::find($sequenceId);

        if ($sequence) {
            switch ($field) {
                case 'accession_number':
                    $sequence->update(['accession_number' => $value]);
                    break;
                case 'length':
                    $sequence->update(['length' => $value]);
                    break;
                case 'method':
                    $sequence->update(['method' => $value]);
                    break;
                case 'instrument':
                    $sequence->update(['instrument' => $value]);
                    break;
                case 'date_sequenced':
                    $sequence->update(['date_sequenced' => $value]);
                    break;
                case 'people_id':
                    $sequence->update(['people_id' => $value]);
                    break;
                case 'laboratories_id':
                    $sequence->update(['laboratories_id' => $value]);
                    break;
                    // Backwards compatibility: old UI used places_id for laboratories_id.
                case 'places_id':
                    $sequence->update(['laboratories_id' => $value]);
                    break;
                case 'projects_id':
                    $sequence->update(['projects_id' => $value]);
                    break;
                case 'fasta_path':
                    $sequence->update(['fasta_path' => $value]);
                    break;
            }

            session()->flash('success', 'Sequence edited successfully!');

            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->sequences = Sequences::with([
            'nucleic_acids',
            'people',
            'laboratories',
            'projects',
        ])->get();
    }
}

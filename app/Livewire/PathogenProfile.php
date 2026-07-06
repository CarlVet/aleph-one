<?php

namespace App\Livewire;

use App\Models\Pathogens;

class PathogenProfile extends PlainComponent
{
    public $pathogen;

    public $id;

    public function mount($id)
    {
        $this->id = $id;
        $this->pathogen = Pathogens::with([
            'protocols',
            'experiments',
        ])->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.pathogen-profile', [
            'pathogen' => $this->pathogen,
        ]);
    }
}

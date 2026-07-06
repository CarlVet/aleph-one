<?php

namespace App\Livewire;

use App\Models\Studies;
use Livewire\Component;

class ShowStudy extends Component
{
    public Studies $study;

    public function mount(Studies $study)
    {
        $this->study = $study;
    }

    public function render()
    {
        return view('livewire.show-study', [
            'study' => Studies::class,
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\NucleicAcids;
use App\Models\Tubes;
use App\Services\TubesService;

class NucleicAcidsLayout extends PlainComponent
{
    public $isEditing = false; // To track editing state

    // Toggle the editing mode
    public function toggleEditMode()
    {
        $this->isEditing = ! $this->isEditing;
    }

    public function render()
    {
        $service = app(TubesService::class);

        $additionalData = $service->assign();

        $query = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', NucleicAcids::class);
        })->with(
            'tubes_content',
            'tubes_content.protocols',
            'tubes_content.people',
            'tubes_content.projects'
        );

        $nucleic_tubes = $query->paginate($this->perPage, pageName: 'articles-page');

        $viewData = array_merge($additionalData, [
            'nucleic_tubes' => $nucleic_tubes,
            'isEditing' => $this->isEditing,
        ]);

        return view('livewire.nucleic-acids-layout', $viewData);
    }
}

<?php

namespace App\Livewire;

use App\Services\ExperimentsService;
use Livewire\WithPagination;

class ExperimentsStatisticsDetailed extends PlainComponent
{
    use WithPagination;

    protected $projectId = 1;

    public function render()
    {
        $service = app(ExperimentsService::class);
        $data = $service->assign();

        return view('livewire.experiments-statistics-detailed', [
            'experiments' => $data['experiments'],
            'experiments_animals' => $data['experiments_animals'],
            'experiments_parasites' => $data['experiments_parasites'],
            'experiments_nucleic' => $data['experiments_nucleic'],
        ]);
    }
}

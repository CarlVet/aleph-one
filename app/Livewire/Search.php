<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Search extends Component
{
    #[Validate('required')]
    public $search_text = '';

    public $results = [];

    public $placeholder;

    public $model; // Specify the model dynamically

    public $search_field = 'title'; // Default search field

    public function updatedSearchText($value)
    {
        $this->reset('results');

        $this->validate();

        $search_term = "%{$value}%";

        // Dynamically determine the model and perform the search
        $modelClass = "App\\Models\\{$this->model}";
        if (class_exists($modelClass)) {
            $this->results = $modelClass::where($this->search_field, 'LIKE', $search_term)->get();
        } else {
            $this->results = [];
        }
    }

    #[On('search:clear-results')]
    public function clear()
    {
        $this->reset('results', 'search_text');
    }

    public function render()
    {
        return view('livewire.search');
    }
}

<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class PhotoUpload extends Component
{
    use WithFileUploads;

    public $photo;

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:2048', // 2MB Max
        ]);

        $path = $this->photo->store('photos', 'local');

        session()->flash('message', 'Photo uploaded successfully.');
    }

    public function render()
    {
        return view('livewire.photo-upload');
    }
}

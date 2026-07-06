<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPaginatedIndex;
use App\Models\TubeRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyTubeRequests extends Component
{
    use WithPaginatedIndex;
    use WithPagination;

    protected function indexPaginationPageName(): string
    {
        return 'page';
    }

    public function render()
    {
        $user = Auth::user();

        if ($user && $user->people) {
            $requests = TubeRequests::with([
                'tube',
                'sourceProject',
                'targetProject',
                'principalInvestigator',
            ])
                ->where('requester_id', $user->people->id)
                ->orderBy('created_at', 'desc')
                ->paginate($this->perPage);
        } else {
            $requests = new LengthAwarePaginator([], 0, $this->perPage, 1, [
                'path' => request()->url(),
            ]);
        }

        return view('livewire.my-tube-requests', [
            'requests' => $requests,
        ]);
    }
}

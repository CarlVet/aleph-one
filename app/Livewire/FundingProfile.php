<?php

namespace App\Livewire;

use App\Models\Fundings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Funding Profile')]
class FundingProfile extends PlainComponent
{
    public Fundings $funding;

    public int $fundingId;

    public bool $canView = false;

    public function mount(Fundings $funding): void
    {
        $this->funding = $funding->load([
            'recipient',
            'projects',
        ]);

        $user = Auth::user();
        $person = $user?->people;

        // Visible if the user belongs to at least one project linked to this funding.
        $this->canView = (bool) ($user && $person && $person->projects()
            ->whereIn('projects.id', $this->funding->projects->pluck('id')->all())
            ->exists());

        if (! $this->canView) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.funding-profile', [
            'funding' => $this->funding,
        ]);
    }
}

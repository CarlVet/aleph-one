<?php

namespace App\Policies;

use App\Models\AnimalSamples;
use App\Models\User;

class AnimalSamplePolicy
{
    public function update(User $user, AnimalSamples $animalSample): bool
    {
        return $animalSample->users->is($user);
    }
}

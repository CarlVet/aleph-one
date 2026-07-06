<?php

namespace Database\Factories;

use App\Models\Lesions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesions>
 */
class LesionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lesions = [
            'Abscess',
            'Ulcer',
            'Necrosis',
            'Granuloma',
            'Hemorrhage',
            'Edema',
            'Inflammation',
            'Fibrosis',
            'Atrophy',
            'Hypertrophy',
            'Hyperplasia',
            'Metaplasia',
            'Dysplasia',
            'Neoplasia',
            'Petechiae',
            'Ecchymosis',
            'Erosion',
            'Fistula',
            'Cyst',
            'Scar',
            'Laceration',
            'Contusion',
            'Abrasion',
            'Puncture',
            'Avulsion',
            'Crush injury',
            'Burn',
            'Frostbite',
            'Gangrene',
            'Sepsis',
            'Cellulitis',
            'Dermatitis',
            'Alopecia',
            'Hyperkeratosis',
            'Pigmentation change',
            'Vesicle',
            'Bulla',
            'Pustule',
            'Papule',
            'Nodule',
            'Tumor',
        ];

        return [
            'name' => $this->faker->randomElement($lesions),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

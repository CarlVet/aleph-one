<?php

namespace Database\Factories;

use App\Models\ClinicalSigns;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalSigns>
 */
class ClinicalSignsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clinicalSigns = [
            'Fever',
            'Anorexia',
            'Lethargy',
            'Weight Loss',
            'Diarrhea',
            'Vomiting',
            'Coughing',
            'Sneezing',
            'Nasal Discharge',
            'Ocular Discharge',
            'Lameness',
            'Dyspnea',
            'Jaundice',
            'Pale Mucous Membranes',
            'Dehydration',
            'Abdominal Pain',
            'Neurological Signs',
            'Skin Lesions',
            'Lymphadenopathy',
            'Anemia',
            'Polyuria',
            'Polydipsia',
            'Polyphagia',
            'Bradycardia',
            'Tachycardia',
            'Arrhythmia',
            'Hypertension',
            'Hypotension',
            'Hypothermia',
            'Hyperthermia',
            'Tachypnea',
            'Bradypnea',
            'Dysuria',
            'Hematuria',
            'Proteinuria',
            'Azotemia',
            'Hyperglycemia',
            'Hypoglycemia',
            'Hypercalcemia',
            'Hypocalcemia',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($clinicalSigns),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

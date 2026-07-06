<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudiesFactory extends Factory
{
    public function definition(): array
    {
        $studies = [
            [
                'ref_key' => 'Hinic2008',
                'title' => 'Novel identification and differentiation of Brucella melitensis, B. abortus, B. suis, B. ovis, B. canis, and B. neotomae suitable for both conventional and real-time PCR systems',
                'abstract' => 'This article illustrates the findings of an epidemiological study on Brucella species in wildlife',
            ],
            [
                'ref_key' => 'Keid2007',
                'title' => 'A polymerase chain reaction for the detection of Brucella canis in semen of naturally infected dogs',
                'abstract' => 'This study reports the efficacy of various diagnostic methods for the detection of Brucella canis in dogs.',
            ],
            [
                'ref_key' => 'Tonetti2009',
                'title' => 'Detection of tick-borne pathogens in South African wildlife',
                'abstract' => 'This study is a fac-simile oiasnvoinasionva',
            ],
        ];

        $randomStudies = $this->faker->unique()->randomElement($studies);

        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        return [
            'ref_key' => $randomStudies['ref_key'],
            'title' => $randomStudies['title'],
            'abstract' => $randomStudies['abstract'],
            'publication_year' => fake()->year($max = 'now'),
            'study_design' => 'Cross-sectional study',
            'users_id' => $userId,
        ];
    }
}

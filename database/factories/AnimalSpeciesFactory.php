<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AnimalSpeciesFactory extends Factory
{
    public function definition(): array
    {
        $species = [
            [
                'name_common' => 'Impala',
                'name_scientific' => 'Aepyceros melampus',
                'genus' => 'Aepyceros',
                'family' => 'Bovidae',
                'order' => 'Artiodactyla',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Lion',
                'name_scientific' => 'Panthera leo',
                'genus' => 'Panthera',
                'family' => 'Felidae',
                'order' => 'Carnivora',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Cheetah',
                'name_scientific' => 'Acinonyx jubatus',
                'genus' => 'Acinonyx',
                'family' => 'Felidae',
                'order' => 'Carnivora',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Elephant',
                'name_scientific' => 'Loxodonta africana',
                'genus' => 'Loxodonta',
                'family' => 'Elephantidae',
                'order' => 'Proboscidea',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Giraffe',
                'name_scientific' => 'Giraffa camelopardalis',
                'genus' => 'Giraffa',
                'family' => 'Giraffidae',
                'order' => 'Artiodactyla',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Leopard',
                'name_scientific' => 'Panthera pardus',
                'genus' => 'Panthera',
                'family' => 'Felidae',
                'order' => 'Carnivora',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
            [
                'name_common' => 'Wild dog',
                'name_scientific' => 'Lycaon pictus',
                'genus' => 'Lycaon',
                'family' => 'Canidae',
                'order' => 'Carnivora',
                'class' => 'Mammalia',
                'phylum' => 'Chordata',
            ],
        ];

        // Pick a random species
        $randomSpecies = $this->faker->unique()->randomElement($species);

        return [
            'name_common' => $randomSpecies['name_common'],
            'name_scientific' => $randomSpecies['name_scientific'],
            'genus' => $randomSpecies['genus'],
            'family' => $randomSpecies['family'],
            'order' => $randomSpecies['order'],
            'class' => $randomSpecies['class'],
            'phylum' => $randomSpecies['phylum'],
        ];
    }
}

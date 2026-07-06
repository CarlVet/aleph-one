<?php

namespace Database\Factories;

use App\Models\EnvironmentSampleTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnvironmentSampleTypes>
 */
class EnvironmentSampleTypesFactory extends Factory
{
    private static $usedNames = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sampleTypes = [
            'Water' => [
                'Surface Water',
                'Groundwater',
                'Rainwater',
                'Drinking Water',
                'Wastewater',
                'River Water',
                'Lake Water',
                'Pond Water',
                'Ocean Water',
                'Spring Water',
            ],
            'Soil' => [
                'Topsoil',
                'Subsoil',
                'Sediment',
                'Mud',
                'Sand',
                'Clay',
                'Agricultural Soil',
                'Forest Soil',
                'Wetland Soil',
                'Desert Soil',
            ],
            'Air' => [
                'Ambient Air',
                'Indoor Air',
                'Dust',
                'Aerosol',
                'Particulate Matter',
                'Air Filter',
                'Bioaerosol',
                'Indoor Dust',
                'Outdoor Dust',
                'Airborne Particles',
            ],
            'Vegetation' => [
                'Plant Surface',
                'Leaf Litter',
                'Grass',
                'Aquatic Plants',
                'Moss',
                'Algae',
                'Biofilm',
                'Vegetation Debris',
                'Plant Roots',
                'Plant Tissue',
            ],
            'Other' => [
                'Surface Swab',
                'Fomite',
                'Food Sample',
                'Feed Sample',
                'Manure',
                'Compost',
                'Parasites',
                'Nest Material',
                'Animal Bedding',
            ],
        ];

        // Get all available types
        $allTypes = [];
        foreach ($sampleTypes as $category => $types) {
            foreach ($types as $type) {
                if (! in_array($type, self::$usedNames)) {
                    $allTypes[] = [
                        'name' => $type,
                        'category' => $category,
                    ];
                }
            }
        }

        // If we've used all types, start adding numbers to make them unique
        if (empty($allTypes)) {
            $category = $this->faker->randomElement(array_keys($sampleTypes));
            $baseType = $this->faker->randomElement($sampleTypes[$category]);
            $counter = 1;
            $type = $baseType;

            while (in_array($type, self::$usedNames)) {
                $type = $baseType.' '.$counter;
                $counter++;
            }

            self::$usedNames[] = $type;

            return [
                'name' => $type,
                'category' => $category,
                'description' => $this->faker->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Select a random unused type
        $selected = $this->faker->randomElement($allTypes);
        self::$usedNames[] = $selected['name'];

        return [
            'name' => $selected['name'],
            'category' => $selected['category'],
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

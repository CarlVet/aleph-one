<?php

namespace Database\Factories;

use App\Models\Organizations;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentsFactory extends Factory
{
    public function definition(): array
    {
        $departmentTypes = ['research', 'administrative', 'clinical', 'academic', 'technical', 'support'];

        $departmentNames = [
            'Research and Development',
            'Clinical Services',
            'Administrative Services',
            'Information Technology',
            'Human Resources',
            'Finance and Accounting',
            'Quality Assurance',
            'Regulatory Affairs',
            'Laboratory Services',
            'Data Management',
            'Biostatistics',
            'Epidemiology',
            'Microbiology',
            'Pathology',
            'Molecular Biology',
            'Immunology',
            'Virology',
            'Parasitology',
            'Toxicology',
            'Environmental Health',
            'Public Health',
            'Veterinary Services',
            'Animal Care',
            'Facilities Management',
            'Safety and Compliance',
            'Training and Education',
            'Communications',
            'Legal Affairs',
            'Procurement',
            'Logistics',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($departmentNames),
            'organizations_id' => Organizations::query()->inRandomOrder()->value('id') ?? Organizations::factory(),
            'department_type' => $this->faker->randomElement($departmentTypes),
            'building' => $this->faker->randomElement(['A', 'B', 'C', 'Main', 'North', 'South', 'East', 'West']).' Building',
            'description' => $this->faker->paragraph(),
        ];
    }
}

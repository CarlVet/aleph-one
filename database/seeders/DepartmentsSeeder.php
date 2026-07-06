<?php

namespace Database\Seeders;

use App\Models\Departments;
use App\Models\Organizations;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        // Get organizations
        $up = Organizations::where('name', 'Example University')->first();
        $izs = Organizations::where('name', 'National Veterinary Research Institute')->first();
        $arc = Organizations::where('name', 'Agricultural Research Institute')->first();
        $nzg = Organizations::where('name', 'National Zoological Institute')->first();

        // Create specific departments
        Departments::create([
            'name' => 'Department of Veterinary Tropical Diseases',
            'organizations_id' => $up->id,
            'department_type' => 'research',
            'building' => 'Main Campus',
            'description' => 'Veterinary research department',
        ]);

        Departments::create([
            'name' => 'Department of Veterinary Science',
            'organizations_id' => $up->id,
            'department_type' => 'academic',
            'building' => 'Main Campus',
            'description' => 'Veterinary academic department',
        ]);

        Departments::create([
            'name' => 'Research Division',
            'organizations_id' => $izs->id,
            'department_type' => 'research',
            'building' => 'Main Building',
            'description' => 'Research division',
        ]);

        Departments::create([
            'name' => 'Diagnostic Services',
            'organizations_id' => $izs->id,
            'department_type' => 'clinical',
            'building' => 'Main Building',
            'description' => 'Diagnostic services department',
        ]);

        Departments::create([
            'name' => 'Veterinary Research Division',
            'organizations_id' => $arc->id,
            'department_type' => 'research',
            'building' => 'Main Campus',
            'description' => 'Veterinary research division',
        ]);

        Departments::create([
            'name' => 'Conservation Research',
            'organizations_id' => $nzg->id,
            'department_type' => 'research',
            'building' => 'Research Building',
            'description' => 'Conservation research department',
        ]);

        Departments::create([
            'name' => 'Central Administration',
            'organizations_id' => $up->id,
            'department_type' => 'administrative',
            'building' => 'Main Building',
            'description' => 'Administrative services department',
        ]);

        // Create additional random departments
        Departments::factory()->count(8)->create();
    }
}

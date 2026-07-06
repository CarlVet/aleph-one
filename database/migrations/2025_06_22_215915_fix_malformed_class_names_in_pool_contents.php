<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix malformed class names in pool_contents table
        DB::table('pool_contents')
            ->where('samples_type', 'AppModelsAnimalSamples')
            ->update(['samples_type' => 'App\\Models\\AnimalSamples']);

        // Also check and fix any other potential malformed class names
        $malformedMappings = [
            'AppModelsHumanSamples' => 'App\\Models\\HumanSamples',
            'AppModelsEnvironmentSamples' => 'App\\Models\\EnvironmentSamples',
            'AppModelsParasiteSamples' => 'App\\Models\\ParasiteSamples',
            'AppModelsNucleicAcids' => 'App\\Models\\NucleicAcids',
            'AppModelsCultures' => 'App\\Models\\Cultures',
            'AppModelsPools' => 'App\\Models\\Pools',
            'AppModelsExperiments' => 'App\\Models\\Experiments',
        ];

        foreach ($malformedMappings as $malformed => $correct) {
            DB::table('pool_contents')
                ->where('samples_type', $malformed)
                ->update(['samples_type' => $correct]);
        }

        // Also check tubes table for malformed class names
        $tubesMalformedMappings = [
            'AppModelsAnimalSamples' => 'App\\Models\\AnimalSamples',
            'AppModelsHumanSamples' => 'App\\Models\\HumanSamples',
            'AppModelsEnvironmentSamples' => 'App\\Models\\EnvironmentSamples',
            'AppModelsParasiteSamples' => 'App\\Models\\ParasiteSamples',
            'AppModelsNucleicAcids' => 'App\\Models\\NucleicAcids',
            'AppModelsCultures' => 'App\\Models\\Cultures',
            'AppModelsPools' => 'App\\Models\\Pools',
            'AppModelsExperiments' => 'App\\Models\\Experiments',
        ];

        foreach ($tubesMalformedMappings as $malformed => $correct) {
            DB::table('tubes')
                ->where('tubes_content_type', $malformed)
                ->update(['tubes_content_type' => $correct]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the fixes (though this might not be necessary)
        $reverseMappings = [
            'App\\Models\\AnimalSamples' => 'AppModelsAnimalSamples',
            'App\\Models\\HumanSamples' => 'AppModelsHumanSamples',
            'App\\Models\\EnvironmentSamples' => 'AppModelsEnvironmentSamples',
            'App\\Models\\ParasiteSamples' => 'AppModelsParasiteSamples',
            'App\\Models\\NucleicAcids' => 'AppModelsNucleicAcids',
            'App\\Models\\Cultures' => 'AppModelsCultures',
            'App\\Models\\Pools' => 'AppModelsPools',
            'App\\Models\\Experiments' => 'AppModelsExperiments',
        ];

        foreach ($reverseMappings as $correct => $malformed) {
            DB::table('pool_contents')
                ->where('samples_type', $correct)
                ->update(['samples_type' => $malformed]);

            DB::table('tubes')
                ->where('tubes_content_type', $correct)
                ->update(['tubes_content_type' => $malformed]);
        }
    }
};

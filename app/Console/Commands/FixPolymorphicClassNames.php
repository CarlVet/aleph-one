<?php

namespace App\Console\Commands;

use App\Models\Cultures;
use App\Models\Experiments;
use App\Models\NucleicAcids;
use App\Models\Parasites;
use App\Models\PoolContents;
use App\Models\Tubes;
use Illuminate\Console\Command;

class FixPolymorphicClassNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:polymorphic-class-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix polymorphic class names that are missing namespace separators';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Fixing polymorphic class names...');

        $totalFixed = 0;

        // Fix pool_contents table
        $fixed = PoolContents::where('samples_type', 'AppModelsHumanSamples')
            ->update(['samples_type' => 'App\Models\HumanSamples']);
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} records in pool_contents table for HumanSamples");
            $totalFixed += $fixed;
        }

        $fixed = PoolContents::where('samples_type', 'AppModelsParasiteSamples')
            ->update(['samples_type' => 'App\Models\ParasiteSamples']);
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} records in pool_contents table for ParasiteSamples");
            $totalFixed += $fixed;
        }

        $fixed = PoolContents::where('samples_type', 'AppModelsAnimalSamples')
            ->update(['samples_type' => 'App\Models\AnimalSamples']);
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} records in pool_contents table for AnimalSamples");
            $totalFixed += $fixed;
        }

        $fixed = PoolContents::where('samples_type', 'AppModelsEnvironmentSamples')
            ->update(['samples_type' => 'App\Models\EnvironmentSamples']);
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} records in pool_contents table for EnvironmentSamples");
            $totalFixed += $fixed;
        }

        $fixed = PoolContents::where('samples_type', 'AppModelsCultures')
            ->update(['samples_type' => 'App\Models\Cultures']);
        if ($fixed > 0) {
            $this->info("✅ Fixed {$fixed} records in pool_contents table for Cultures");
            $totalFixed += $fixed;
        }

        // Check other tables for similar issues
        $tables = [
            'tubes' => ['column' => 'tubes_content_type', 'model' => Tubes::class],
            'cultures' => ['column' => 'cultures_content_type', 'model' => Cultures::class],
            'experiments' => ['column' => 'experiments_content_type', 'model' => Experiments::class],
            'parasites' => ['column' => 'parasites_origin_type', 'model' => Parasites::class],
            'nucleic_acids' => ['column' => 'nucleic_content_type', 'model' => NucleicAcids::class],
        ];

        $issuesFound = 0;
        foreach ($tables as $tableName => $config) {
            $model = $config['model'];
            $column = $config['column'];

            // Check for any class names without proper namespace separators
            $incorrectRecords = $model::where($column, 'like', 'AppModels%')->count();
            if ($incorrectRecords > 0) {
                $this->warn("⚠️  Found {$incorrectRecords} records in {$tableName} table with incorrect class names");
                $issuesFound += $incorrectRecords;
            }
        }

        if ($totalFixed > 0) {
            $this->info("🎉 Successfully fixed {$totalFixed} records!");
        } else {
            $this->info('✅ No records needed fixing - all class names are correct!');
        }

        if ($issuesFound > 0) {
            $this->warn("⚠️  Found {$issuesFound} additional records that may need attention in other tables");
        }

        $this->info('✨ Polymorphic class names check completed!');
    }
}

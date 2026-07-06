<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MaintainDatabase extends Command
{
    protected $signature = 'db:maintain';

    protected $description = 'Maintain SQLite database to prevent corruption';

    public function handle()
    {
        $this->info('Maintaining SQLite database...');

        try {
            // Optimize the database
            DB::statement('PRAGMA optimize');
            $this->info('✓ Database optimized');

            // Analyze tables for better query performance
            DB::statement('ANALYZE');
            $this->info('✓ Tables analyzed');

            // Check database integrity
            $integrity = DB::select('PRAGMA integrity_check');
            if ($integrity[0]->integrity_check === 'ok') {
                $this->info('✓ Database integrity verified');
            } else {
                $this->error('✗ Database integrity check failed: '.$integrity[0]->integrity_check);

                return 1;
            }

            // Vacuum the database to reclaim space and optimize
            DB::statement('VACUUM');
            $this->info('✓ Database vacuumed');

            $this->info('Database maintenance completed successfully!');

            return 0;

        } catch (\Exception $e) {
            $this->error('Database maintenance failed: '.$e->getMessage());

            return 1;
        }
    }
}

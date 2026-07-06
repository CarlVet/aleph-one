<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Citable scientific records that receive a persistent, globally-unique
     * public identifier. Internal codes (e.g. A1B2-AS-190) are not globally
     * persistent, which weakens FAIR "Findable"; the UUID closes that gap.
     *
     * @var list<string>
     */
    private array $tables = ['projects', 'animal_samples', 'human_samples', 'experiments', 'sequences'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('uuid')->nullable()->after('id')->unique();
            });

            DB::table($table)->whereNull('uuid')->orderBy('id')->pluck('id')->each(function ($id) use ($table): void {
                DB::table($table)->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique($table.'_uuid_unique');
                $blueprint->dropColumn('uuid');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('cultures') || ! $this->tableExists('culture_photos')) {
            return;
        }

        $existingCultureIds = DB::table('culture_photos')
            ->select('cultures_id')
            ->distinct()
            ->pluck('cultures_id')
            ->all();

        $legacyCultures = DB::table('cultures')
            ->select('id', 'photo_path', 'people_id', 'created_at', 'updated_at')
            ->whereNotNull('photo_path')
            ->where('photo_path', '<>', '')
            ->when($existingCultureIds !== [], fn ($query) => $query->whereNotIn('id', $existingCultureIds))
            ->orderBy('id')
            ->get();

        $now = now();

        foreach ($legacyCultures as $culture) {
            DB::table('culture_photos')->insert([
                'cultures_id' => $culture->id,
                'photo_path' => $culture->photo_path,
                'observed_at' => null,
                'notes' => null,
                'people_id' => $culture->people_id,
                'created_at' => $culture->created_at ?? $now,
                'updated_at' => $culture->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive data backfill; no rollback.
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
};

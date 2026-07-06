<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('culture_photos', function (Blueprint $table) {
            $table->foreignId('culture_observations_id')
                ->nullable()
                ->after('cultures_id')
                ->constrained('culture_observations')
                ->cascadeOnDelete();
        });

        if (! Schema::hasTable('culture_photos')) {
            return;
        }

        $photos = DB::table('culture_photos')->orderBy('id')->get();

        foreach ($photos as $photo) {
            $observationId = DB::table('culture_observations')->insertGetId([
                'cultures_id' => $photo->cultures_id,
                'observed_at' => $photo->observed_at,
                'notes' => $photo->notes,
                'people_id' => $photo->people_id,
                'created_at' => $photo->created_at,
                'updated_at' => $photo->updated_at,
            ]);

            DB::table('culture_photos')
                ->where('id', $photo->id)
                ->update(['culture_observations_id' => $observationId]);
        }

        Schema::table('culture_photos', function (Blueprint $table) {
            $table->dropForeign(['people_id']);
            $table->dropColumn(['observed_at', 'notes', 'people_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('culture_photos')) {
            return;
        }

        Schema::table('culture_photos', function (Blueprint $table) {
            $table->date('observed_at')->nullable()->after('photo_path');
            $table->text('notes')->nullable()->after('observed_at');
            $table->foreignId('people_id')->nullable()->after('notes')->constrained('people')->nullOnDelete();
        });

        $photos = DB::table('culture_photos')
            ->whereNotNull('culture_observations_id')
            ->orderBy('id')
            ->get();

        foreach ($photos as $photo) {
            $observation = DB::table('culture_observations')->where('id', $photo->culture_observations_id)->first();
            if (! $observation) {
                continue;
            }

            DB::table('culture_photos')
                ->where('id', $photo->id)
                ->update([
                    'observed_at' => $observation->observed_at,
                    'notes' => $observation->notes,
                    'people_id' => $observation->people_id,
                ]);
        }

        Schema::table('culture_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('culture_observations_id');
        });
    }
};

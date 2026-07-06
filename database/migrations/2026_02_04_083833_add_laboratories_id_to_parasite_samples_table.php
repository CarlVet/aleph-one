<?php

use App\Models\Laboratories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parasite_samples', function (Blueprint $table) {
            $table->foreignIdFor(Laboratories::class)
                ->nullable()
                ->after('people_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parasite_samples', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Laboratories::class);
        });
    }
};

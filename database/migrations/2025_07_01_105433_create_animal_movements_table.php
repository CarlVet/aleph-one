<?php

use App\Models\Animals;
use App\Models\SamplingSites;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Animals::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SamplingSites::class, 'source_sampling_site_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SamplingSites::class, 'destination_sampling_site_id')->constrained()->cascadeOnDelete();
            $table->date('date_moved');
            $table->decimal('coordinates_start_lat', 10, 8)->nullable();
            $table->decimal('coordinates_start_lng', 11, 8)->nullable();
            $table->decimal('coordinates_destination_lat', 10, 8)->nullable();
            $table->decimal('coordinates_destination_lng', 11, 8)->nullable();
            $table->string('movement_reason')->nullable(); // e.g., 'Relocation', 'Treatment', 'Breeding', 'Research'
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_movements');
    }
};

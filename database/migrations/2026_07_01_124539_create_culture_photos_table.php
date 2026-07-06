<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culture_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cultures_id')->constrained('cultures')->cascadeOnDelete();
            $table->string('photo_path');
            $table->date('observed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('people_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('culture_photos');
    }
};

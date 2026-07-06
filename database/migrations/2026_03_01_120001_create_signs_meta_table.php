<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signs_meta', function (Blueprint $table): void {
            $table->id();
            $table->morphs('meta');
            $table->foreignId('clinical_signs_id')->constrained('clinical_signs')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['meta_type', 'meta_id', 'clinical_signs_id'], 'signs_meta_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signs_meta');
    }
};

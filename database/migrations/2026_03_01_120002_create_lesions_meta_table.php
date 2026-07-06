<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesions_meta', function (Blueprint $table): void {
            $table->id();
            $table->morphs('meta');
            $table->foreignId('lesions_id')->constrained('lesions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['meta_type', 'meta_id', 'lesions_id'], 'lesions_meta_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesions_meta');
    }
};

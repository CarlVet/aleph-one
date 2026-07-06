<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_factors_meta', function (Blueprint $table): void {
            $table->id();
            $table->morphs('meta');
            $table->foreignId('risk_factors_id')->constrained('risk_factors')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['meta_type', 'meta_id', 'risk_factors_id'], 'risk_factors_meta_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_factors_meta');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pathogens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ncbi_tax_id')->nullable();
            $table->string('species')->unique();
            $table->string('genus');
            $table->string('family');
            $table->string('order');
            $table->string('class');
            $table->string('phylum');
            $table->string('kingdom');
            $table->string('domain');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pathogens');
    }
};

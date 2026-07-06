<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parasite_species', function (Blueprint $table) {
            $table->id();
            $table->string('name_scientific')->unique();
            $table->string('name_common')->nullable()->unique();
            $table->string('genus')->nullable();
            $table->string('family')->nullable();
            $table->string('order')->nullable();
            $table->string('class')->nullable();
            $table->string('phylum')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parasite_species');
    }
};

<?php

use App\Models\Laboratories;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->string('room')->nullable();
            $table->string('barcode')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

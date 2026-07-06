<?php

use App\Models\Countries;
use App\Models\Organizations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(Organizations::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Countries::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('lab_type')->nullable(); // research, diagnostic, commercial, etc.
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratories');
    }
};

<?php

use App\Models\Countries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->default('company'); // company, government, university, ngo, etc.
            $table->foreignIdFor(Countries::class)->constrained()->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};

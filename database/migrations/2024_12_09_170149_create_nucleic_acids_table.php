<?php

use App\Models\Laboratories;
use App\Models\People;
use App\Models\Projects;
use App\Models\Protocols;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nucleic_acids', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type');
            $table->morphs('nucleic_content');
            $table->foreignIdFor(Protocols::class)->constrained()->cascadeOnDelete();
            $table->date('date_extracted');
            $table->unsignedInteger('volume')->nullable();
            $table->decimal('concentration', 6, 2)->nullable();
            $table->decimal('A260/A280', 3, 2)->nullable();
            $table->decimal('A260/A230', 3, 2)->nullable();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nucleic_acids');
    }
};

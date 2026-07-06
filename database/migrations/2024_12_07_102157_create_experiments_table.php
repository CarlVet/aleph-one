<?php

use App\Models\Laboratories;
use App\Models\Pathogens;
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
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->morphs('experiments_content');
            $table->foreignIdFor(Protocols::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Pathogens::class)->constrained()->cascadeOnDelete();
            $table->string('outcome_discrete');
            $table->decimal('outcome_quant', 4, 2)->nullable();
            $table->boolean('outcome_binary')->nullable();
            $table->date('date_tested');
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};

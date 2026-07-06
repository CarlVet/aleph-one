<?php

use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('accession_number')->nullable();
            $table->foreignIdFor(NucleicAcids::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('length');
            $table->string('method');
            $table->string('instrument');
            $table->date('date_sequenced');
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->string('fasta_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};

<?php

use App\Models\Laboratories;
use App\Models\People;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('cultures')->onDelete('cascade');
            $table->morphs('cultures_content');
            $table->integer('step');
            $table->date('date_cultured')->nullable();
            $table->string('medium')->nullable();
            $table->string('type')->nullable();
            $table->integer('incubation_temp')->nullable();
            $table->string('athmosphere')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultures');
    }
};

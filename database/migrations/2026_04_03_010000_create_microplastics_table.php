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
        Schema::create('microplastics', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->morphs('microplastics_content');
            $table->decimal('sample_weight', 10, 3)->nullable();
            $table->decimal('r_coeff', 6, 4)->nullable();
            $table->string('mps_type')->nullable();
            $table->decimal('m_feret', 10, 3)->nullable();
            $table->foreignIdFor(Protocols::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Laboratories::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microplastics');
    }
};

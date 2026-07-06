<?php

use App\Models\Pools;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_contents', function (Blueprint $table) {
            $table->id();
            $table->morphs('samples');
            $table->foreignIdFor(Pools::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_contents');
    }
};

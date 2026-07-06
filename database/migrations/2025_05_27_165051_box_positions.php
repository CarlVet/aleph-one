<?php

use App\Models\Boxes;
use App\Models\Locations;
use App\Models\People;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Boxes::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Locations::class)->constrained()->cascadeOnDelete();
            $table->string('sublocation')->nullable();
            $table->date('date_moved');
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_positions');
    }
};

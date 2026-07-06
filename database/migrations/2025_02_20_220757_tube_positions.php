<?php

use App\Models\Boxes;
use App\Models\People;
use App\Models\Tubes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tubes::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Boxes::class)->constrained()->cascadeOnDelete();
            $table->integer('position_x');
            $table->integer('position_y');
            $table->date('date_moved');
            $table->foreignIdFor(People::class)->constrained()->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tube_positions');
    }
};

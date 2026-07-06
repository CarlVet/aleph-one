<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mps_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('mps_types')->insert([
            ['name' => 'Polyamide', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polycarbonate', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polyester', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polyethylene', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polypropylene', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polystyrene', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Polyurethane', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'PVC', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mps_types');
    }
};

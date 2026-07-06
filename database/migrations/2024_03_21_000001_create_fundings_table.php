<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable();
            $table->foreignId('recipient_id')->constrained('people')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency')->default('ZAR');
            $table->string('reference')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('fundings');
    }
};

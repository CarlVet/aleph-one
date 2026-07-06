<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('protocol_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocols_id')->constrained('protocols')->cascadeOnDelete();
            $table->foreignId('users_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('protocol_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['protocols_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol_comments');
    }
};

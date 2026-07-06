<?php

use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // e.g., 'sample_created', 'experiment_created', 'tube_moved'
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable(); // Link to the related item
            $table->boolean('read')->default(false);
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

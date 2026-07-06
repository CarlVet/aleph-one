<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culture_photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_photos_id')->constrained('culture_photos')->cascadeOnDelete();
            $table->foreignId('users_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('culture_photos_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('culture_photo_comments');
    }
};

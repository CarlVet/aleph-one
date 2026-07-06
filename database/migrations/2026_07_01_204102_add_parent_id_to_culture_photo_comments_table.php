<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('culture_photo_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('users_id')
                ->constrained('culture_photo_comments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('culture_photo_comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};

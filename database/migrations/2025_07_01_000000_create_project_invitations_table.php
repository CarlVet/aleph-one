<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('people_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('role');
            $table->string('permission');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
    }
};

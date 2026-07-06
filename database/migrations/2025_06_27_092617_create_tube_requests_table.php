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
        Schema::create('tube_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tubes_id')->constrained('tubes')->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('people')->onDelete('cascade'); // Person requesting the tube
            $table->foreignId('source_project_id')->constrained('projects')->onDelete('cascade'); // Original project
            $table->foreignId('target_project_id')->constrained('projects')->onDelete('cascade'); // Project to transfer to
            $table->foreignId('principal_investigator_id')->constrained('people')->onDelete('cascade'); // PI of source project
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('request_message')->nullable(); // Optional message from requester
            $table->text('response_message')->nullable(); // Optional response from PI
            $table->timestamp('responded_at')->nullable(); // When PI responded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tube_requests');
    }
};

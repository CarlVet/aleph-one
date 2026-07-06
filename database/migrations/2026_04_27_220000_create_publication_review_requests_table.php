<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_review_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projects_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('data_type');
            $table->string('literature_type')->nullable();
            $table->string('status')->default('pending');
            $table->text('requester_message')->nullable();
            $table->text('reviewer_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['projects_id', 'status']);
            $table->index(['requester_user_id', 'status']);
        });

        Schema::create('publication_review_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_review_request_id')->constrained('publication_review_requests')->cascadeOnDelete();
            $table->string('reviewable_type');
            $table->unsignedBigInteger('reviewable_id');
            $table->string('code')->nullable();
            $table->string('summary')->nullable();
            $table->timestamps();

            $table->unique(['publication_review_request_id', 'reviewable_type', 'reviewable_id'], 'publication_review_request_items_unique');
            $table->index(['reviewable_type', 'reviewable_id'], 'publication_review_request_items_reviewable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_review_request_items');
        Schema::dropIfExists('publication_review_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projects_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('type'); // proposal, ethics_approval, report, etc.
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->text('description')->nullable();
            $table->date('document_date')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('documents')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
        Schema::dropIfExists('documents');
    }
};

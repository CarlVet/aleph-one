<?php

use App\Models\Departments;
use App\Models\Organizations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_birth')->nullable();
            $table->foreignIdFor(Departments::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Organizations::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->unique()->nullable();
            $table->string('job')->nullable();
            $table->string('pic_path')->nullable();
            $table->string('orcid')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};

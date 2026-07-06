<?php

use App\Models\Countries;
use App\Models\Projects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('humans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('sex')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('ethnicity')->nullable();
            $table->string('occupation')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignIdFor(Countries::class)->constrained()->cascadeOnDelete();
            $table->string('preferred_contact_method')->nullable(); // e.g., 'phone', 'email', 'sms'
            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();
            $table->string('alternate_email')->nullable();
            $table->string('national_id')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignIdFor(Projects::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('humans');
    }
};

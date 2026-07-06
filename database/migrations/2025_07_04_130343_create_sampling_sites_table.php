<?php

use App\Models\Countries;
use App\Models\Organizations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampling_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(Organizations::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Countries::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('site_type')->nullable(); // national_park, reserve, farm, zoo, etc.
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampling_sites');
    }
};

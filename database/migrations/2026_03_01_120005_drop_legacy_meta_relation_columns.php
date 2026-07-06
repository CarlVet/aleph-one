<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_animals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('risk_factors_id');
            $table->dropConstrainedForeignId('clinical_signs_id');
            $table->dropConstrainedForeignId('lesions_id');
        });

        Schema::table('meta_humans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('risk_factors_id');
            $table->dropConstrainedForeignId('clinical_signs_id');
            $table->dropConstrainedForeignId('lesions_id');
        });

        Schema::table('meta_parasites', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('risk_factors_id');
        });

        Schema::table('meta_environments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('risk_factors_id');
        });
    }

    public function down(): void
    {
        Schema::table('meta_animals', function (Blueprint $table): void {
            $table->foreignId('risk_factors_id')->nullable()->constrained('risk_factors')->onDelete('cascade');
            $table->foreignId('clinical_signs_id')->nullable()->constrained('clinical_signs')->onDelete('cascade');
            $table->foreignId('lesions_id')->nullable()->constrained('lesions')->onDelete('cascade');
        });

        Schema::table('meta_humans', function (Blueprint $table): void {
            $table->foreignId('risk_factors_id')->nullable()->constrained('risk_factors')->onDelete('cascade');
            $table->foreignId('clinical_signs_id')->nullable()->constrained('clinical_signs')->onDelete('cascade');
            $table->foreignId('lesions_id')->nullable()->constrained('lesions')->onDelete('cascade');
        });

        Schema::table('meta_parasites', function (Blueprint $table): void {
            $table->foreignId('risk_factors_id')->nullable()->constrained('risk_factors')->onDelete('cascade');
        });

        Schema::table('meta_environments', function (Blueprint $table): void {
            $table->foreignId('risk_factors_id')->nullable()->constrained('risk_factors')->onDelete('cascade');
        });
    }
};

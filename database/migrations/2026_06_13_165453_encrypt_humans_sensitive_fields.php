<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen the columns that now hold encrypted payloads (Laravel's encrypted
     * cast produces ~200+ char base64 blobs that would overflow a varchar on
     * MySQL) and add a deterministic blind index so national_id can still be
     * looked up by equality after it is encrypted.
     */
    public function up(): void
    {
        Schema::table('humans', function (Blueprint $table): void {
            $table->text('national_id')->nullable()->change();
            $table->text('alternate_phone')->nullable()->change();
            $table->text('alternate_email')->nullable()->change();
            $table->string('national_id_hash', 64)->nullable()->after('national_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('humans', function (Blueprint $table): void {
            $table->dropIndex(['national_id_hash']);
            $table->dropColumn('national_id_hash');
            $table->string('national_id')->nullable()->change();
            $table->string('alternate_phone')->nullable()->change();
            $table->string('alternate_email')->nullable()->change();
        });
    }
};

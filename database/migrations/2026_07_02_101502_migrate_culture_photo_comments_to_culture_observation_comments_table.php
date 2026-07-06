<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culture_observation_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_observations_id')->constrained('culture_observations')->cascadeOnDelete();
            $table->foreignId('users_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('culture_observation_comments')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('culture_observations_id');
        });

        if (! Schema::hasTable('culture_photo_comments')) {
            return;
        }

        $comments = DB::table('culture_photo_comments')->orderBy('id')->get();
        $idMap = [];

        foreach ($comments as $comment) {
            $photo = DB::table('culture_photos')->where('id', $comment->culture_photos_id)->first();
            if (! $photo || ! $photo->culture_observations_id) {
                continue;
            }

            $newId = DB::table('culture_observation_comments')->insertGetId([
                'culture_observations_id' => $photo->culture_observations_id,
                'users_id' => $comment->users_id,
                'parent_id' => null,
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ]);

            $idMap[$comment->id] = $newId;
        }

        foreach ($comments as $comment) {
            if ($comment->parent_id === null || ! isset($idMap[$comment->id], $idMap[$comment->parent_id])) {
                continue;
            }

            DB::table('culture_observation_comments')
                ->where('id', $idMap[$comment->id])
                ->update(['parent_id' => $idMap[$comment->parent_id]]);
        }

        Schema::drop('culture_photo_comments');
    }

    public function down(): void
    {
        Schema::create('culture_photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_photos_id')->constrained('culture_photos')->cascadeOnDelete();
            $table->foreignId('users_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('culture_photos_id');
        });

        if (! Schema::hasTable('culture_observation_comments')) {
            return;
        }

        Schema::table('culture_photo_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('users_id')->constrained('culture_photo_comments')->cascadeOnDelete();
        });

        $comments = DB::table('culture_observation_comments')->orderBy('id')->get();
        $idMap = [];

        foreach ($comments as $comment) {
            $photo = DB::table('culture_photos')
                ->where('culture_observations_id', $comment->culture_observations_id)
                ->orderBy('id')
                ->first();

            if (! $photo) {
                continue;
            }

            $newId = DB::table('culture_photo_comments')->insertGetId([
                'culture_photos_id' => $photo->id,
                'users_id' => $comment->users_id,
                'parent_id' => null,
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
            ]);

            $idMap[$comment->id] = $newId;
        }

        foreach ($comments as $comment) {
            if ($comment->parent_id === null || ! isset($idMap[$comment->id], $idMap[$comment->parent_id])) {
                continue;
            }

            DB::table('culture_photo_comments')
                ->where('id', $idMap[$comment->id])
                ->update(['parent_id' => $idMap[$comment->parent_id]]);
        }

        Schema::drop('culture_observation_comments');
    }
};

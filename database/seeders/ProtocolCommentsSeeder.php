<?php

namespace Database\Seeders;

use App\Models\ProtocolComments;
use App\Models\Protocols;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProtocolCommentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ProtocolComments::query()->exists()) {
            return;
        }

        $users = User::query()->get();
        if ($users->isEmpty()) {
            return;
        }

        $protocols = Protocols::query()->orderBy('id')->limit(25)->get();
        foreach ($protocols as $protocol) {
            $topLevelCount = rand(1, 3);

            for ($i = 0; $i < $topLevelCount; $i++) {
                $top = ProtocolComments::factory()->create([
                    'protocols_id' => $protocol->id,
                    'users_id' => $users->random()->id,
                    'parent_id' => null,
                ]);

                $replyCount = rand(0, 3);
                if ($replyCount > 0) {
                    ProtocolComments::factory()
                        ->count($replyCount)
                        ->create([
                            'protocols_id' => $protocol->id,
                            'users_id' => $users->random()->id,
                            'parent_id' => $top->id,
                        ]);
                }
            }
        }
    }
}

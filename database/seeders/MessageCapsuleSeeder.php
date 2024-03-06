<?php

namespace Database\Seeders;

use App\Models\MessageCapsule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A demo account with capsules in all three states, so that a fresh install
 * shows something other than an empty list.
 */
class MessageCapsuleSeeder extends Seeder
{
    public const DEMO_EMAIL = 'demo@example.com';

    public const DEMO_PASSWORD = 'correct-horse-battery-staple';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Demo User', 'password' => self::DEMO_PASSWORD],
        );

        // Read already.
        MessageCapsule::factory()->recycle($user)->opened()->create([
            'note' => 'You were nervous about the interview. You did fine.',
            'scheduled_opening_time' => now()->subMonth(),
        ]);

        // Unlocked, waiting to be opened.
        MessageCapsule::factory()->recycle($user)->unlocked()->create([
            'note' => 'Remember to call your sister on her birthday.',
            'scheduled_opening_time' => now()->subHours(2),
        ]);

        // Still sealed.
        MessageCapsule::factory()->recycle($user)->create([
            'note' => 'Tomorrow you will be proud of how patient you were.',
            'scheduled_opening_time' => now()->addDays(3),
        ]);

        MessageCapsule::factory()->recycle($user)->create([
            'note' => 'One year in. Did you keep the habit?',
            'scheduled_opening_time' => now()->addYear(),
        ]);
    }
}

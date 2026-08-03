<?php

namespace Database\Seeders;

use App\Models\HuntedDeal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the production database with minimal demo data.
     */
    public function run(): void
    {
        // Only create demo user if it doesn't exist
        $demoUser = User::where('email', 'demo@olx-deal-hunter.com')->first();

        if (! $demoUser) {
            $demoUser = User::create([
                'name' => 'Demo User',
                'email' => 'demo@olx-deal-hunter.com',
                'password' => Hash::make(env('DEMO_PASSWORD', 'demo123')),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]);

            // Create one sample hunted deal
            HuntedDeal::create([
                'user_id' => $demoUser->id,
                'search_term' => 'iPhone',
                'notes' => 'Sample search for iPhone deals',
                'is_active' => true,
            ]);

            $this->command->info('Production demo user created');
        } else {
            $this->command->info('Demo user already exists, skipping seeding');
        }
    }
}

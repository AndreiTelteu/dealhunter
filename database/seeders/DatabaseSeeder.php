<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\HuntedDeal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo user
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@olx-deal-hunter.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
            ]
        );

        // Create sample hunted deals for demo user
        $huntedDeals = [
            [
                'search_term' => 'iPhone 13',
                'notes' => 'Looking for iPhone 13 in good condition, preferably unlocked',
                'is_active' => true,
            ],
            [
                'search_term' => 'MacBook Pro',
                'notes' => 'MacBook Pro 2020 or newer, minimum 16GB RAM',
                'is_active' => true,
            ],
            [
                'search_term' => 'PlayStation 5',
                'notes' => 'PS5 console with controllers and games',
                'is_active' => false,
            ],
            [
                'search_term' => 'Samsung Galaxy S23',
                'notes' => 'Latest Samsung flagship phone',
                'is_active' => true,
            ],
            [
                'search_term' => 'Nintendo Switch',
                'notes' => 'Nintendo Switch console with popular games',
                'is_active' => true,
            ],
        ];

        foreach ($huntedDeals as $huntedDealData) {
            HuntedDeal::firstOrCreate(
                [
                    'user_id' => $demoUser->id,
                    'search_term' => $huntedDealData['search_term'],
                ],
                $huntedDealData
            );
        }

        $this->command->info('Demo user created: demo@olx-deal-hunter.com / demo123');
        $this->command->info('Sample hunted deals created for demo user');
    }
}

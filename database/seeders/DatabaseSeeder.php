<?php

namespace Database\Seeders;

use App\Models\HuntedDeal;
use App\Models\User;
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
            ['email' => 'andrei@telteu.ro'],
            [
                'name' => 'AndreiTelteu',
                'password' => Hash::make('demo123'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        // Create sample hunted deals for demo user
        $huntedDeals = [
            [
                'search_term' => 'rx 7900 xtx',
                'is_active' => true,
            ],
            [
                'search_term' => 'rtx 4080',
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

        $this->command->info('Demo user created: andrei@telteu.ro / demo123');
        $this->command->info('Sample hunted deals created for demo user');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buyer: Has 1000 USD, 0 BTC
        $buyer = \App\Models\User::firstOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Buyer Account',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'balance' => 1000.00,
            ]
        );
        $this->command->info("Buyer seeded: {$buyer->email} ($1000)");

        // Seller: Has 0 USD, 10 BTC
        $seller = \App\Models\User::firstOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Seller Account',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'balance' => 0.00,
            ]
        );

        \App\Models\Asset::firstOrCreate(
            ['user_id' => $seller->id, 'symbol' => 'BTC'],
            ['amount' => 10.00, 'locked_amount' => 0.00]
        );

        $this->command->info("Seller seeded: {$seller->email} (10 BTC)");
    }
}

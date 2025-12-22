<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buyer: Has 1000 USD, 0 BTC
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Buyer Account',
                'password' => Hash::make('password'),
                'balance' => 1000.00,
            ]
        );
        $this->command->info("Buyer seeded: {$buyer->email} ($1000)");

        // Seller: Has 0 USD, 10 BTC
        $seller = User::firstOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Seller Account',
                'password' => Hash::make('password'),
                'balance' => 0.00,
            ]
        );

        Asset::firstOrCreate(
            ['user_id' => $seller->id, 'symbol' => 'BTC'],
            ['amount' => 10.00, 'locked_amount' => 0.00]
        );

        $this->command->info("Seller seeded: {$seller->email} (10 BTC)");
    }
}

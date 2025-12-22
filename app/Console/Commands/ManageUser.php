<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ManageUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage {search? : The ID or email of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Admin console to manage user balances and assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $search = $this->argument('search');

        if (! $search) {
            $search = \Laravel\Prompts\text('Enter User ID or Email to manage:');
        }

        $user = \App\Models\User::where('id', $search)
            ->orWhere('email', $search)
            ->first();

        if (! $user) {
            $this->error("User not found: {$search}");

            return;
        }

        $this->info("Managing User: {$user->name} ({$user->email})");

        while (true) {
            $user->refresh();

            $choice = \Laravel\Prompts\select(
                label: 'What would you like to do?',
                options: [
                    'view' => 'View Balances',
                    'usd' => 'Update USD Balance',
                    'asset' => 'Fund/Update Asset',
                    'exit' => 'Exit',
                ]
            );

            if ($choice === 'exit') {
                break;
            }

            if ($choice === 'view') {
                $this->table(
                    ['Type', 'Value'],
                    [
                        ['USD Balance', number_format($user->balance, 2)],
                        ...$user->assets->map(fn ($a) => [$a->symbol, number_format($a->amount, 8).' (Locked: '.number_format($a->locked_amount, 8).')']),
                    ]
                );
            } elseif ($choice === 'usd') {
                $amount = \Laravel\Prompts\text(
                    label: 'Enter new USD Balance (Current: '.$user->balance.')',
                    placeholder: '1000',
                    validate: fn ($value) => is_numeric($value) ? null : 'Must be a number'
                );

                $user->balance = $amount;
                $user->save();
                $this->info("USD Balance updated to {$amount}");
            } elseif ($choice === 'asset') {
                $symbol = strtoupper(\Laravel\Prompts\text('Asset Symbol (e.g. BTC):'));
                $amount = \Laravel\Prompts\text('New Amount:', validate: fn ($value) => is_numeric($value) ? null : 'Must be a number');

                $asset = \App\Models\Asset::firstOrCreate(
                    ['user_id' => $user->id, 'symbol' => $symbol],
                    ['amount' => 0, 'locked_amount' => 0]
                );

                $asset->amount = $amount;
                $asset->save();
                $this->info("{$symbol} Balance updated to {$amount}");
            }
        }
    }
}

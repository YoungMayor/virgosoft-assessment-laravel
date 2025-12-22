<?php

namespace App\Services;

use App\Exceptions\ClientException;
use App\Jobs\MatchOrders;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order for the user.
     *
     * @throws Exception
     */
    public function createOrder(User $user, array $data): Order
    {
        $price = $data['price'];
        $amount = $data['amount'];
        $side = $data['side'];
        $symbol = $data['symbol'];

        $totalCost = $price * $amount;
        $order = null;

        try {
            DB::transaction(function () use ($user, $symbol, $side, $price, $amount, $totalCost, &$order) {
                // Refresh user for lock
                $user = User::lockForUpdate()->find($user->id);

                if ($side === 'buy') {
                    if ($user->balance < $totalCost) {
                        throw new ClientException('Insufficient USD balance.');
                    }

                    $user->balance -= $totalCost;
                    $user->save();
                } else {
                    // Sell Order
                    $asset = Asset::lockForUpdate()
                        ->where('user_id', $user->id)
                        ->where('symbol', $symbol)
                        ->first();

                    if (! $asset || $asset->amount < $amount) {
                        throw new ClientException('Insufficient asset balance.');
                    }

                    $asset->amount -= $amount;
                    $asset->locked_amount += $amount;
                    $asset->save();
                }

                $order = $user->orders()->create([
                    'symbol' => $symbol,
                    'side' => $side,
                    'price' => $price,
                    'amount' => $amount,
                    'status' => 'open',
                ]);
            });
        } catch (Exception $e) {
            throw $e;
        }

        // Trigger matching synchronously as per simple requirement, or dispatch job.
        // For "Real-time" and "Atomic", sync is fine for MVP.
        MatchOrders::dispatch($order);

        return $order;
    }

    /**
     * Cancel an existing order.
     *
     * @throws Exception
     */
    public function cancelOrder(User $user, int $orderId): void
    {
        $order = $user->orders()->where('id', $orderId)->firstOrFail();

        if ($order->status !== 'open') {
            throw new ClientException('Order is not open.');
        }

        DB::transaction(function () use ($order, $user) {
            // Lock user to ensure balance consistency
            $user = User::lockForUpdate()->find($user->id);
            // Refresh order logic within transaction?
            // Re-fetch order to lock it?
            // In original code: $user = User::lockForUpdate()->find($order->user_id);
            // But here we passed $user. Let's ensure strict consistency.

            // We should reload order with lock if we want to be super safe against race conditions on status
            // BUT $order->refresh() inside transaction doesn't lock row unless we select for update.
            $order = Order::lockForUpdate()->find($order->id);

            if ($order->status !== 'open') {
                return;
            }

            if ($order->side === 'buy') {
                $cost = $order->price * $order->amount;
                $user->balance += $cost;
                $user->save();
            } else {
                $asset = Asset::lockForUpdate()
                    ->where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->first();

                if ($asset) {
                    $asset->amount += $order->amount;
                    $asset->locked_amount -= $order->amount;
                    $asset->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();
        });
    }
}

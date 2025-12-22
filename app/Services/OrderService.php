<?php

namespace App\Services;

use App\Enums\OrderStatus;
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
        return DB::transaction(function () use ($user, $data) {
            $user = User::lockForUpdate()->find($user->id);

            $this->validateAndDeductFunds($user, $data);

            $order = $user->orders()->create([
                'symbol' => $data['symbol'],
                'side' => $data['side'],
                'price' => $data['price'],
                'amount' => $data['amount'],
                'status' => OrderStatus::Open,
            ]);

            // Trigger matching
            MatchOrders::dispatch($order);

            return $order;
        });
    }

    /**
     * Cancel an existing order.
     *
     * @throws Exception
     */
    public function cancelOrder(User $user, int $orderId): void
    {
        DB::transaction(function () use ($user, $orderId) {
            $user = User::lockForUpdate()->find($user->id);
            $order = Order::lockForUpdate()->find($orderId);

            if (! $order || $order->user_id !== $user->id) {
                throw new ClientException('Order not found.');
            }

            if ($order->status !== OrderStatus::Open) {
                // If already processed, we can't cancel.
                throw new ClientException('Order is not open.');
            }

            $this->refundFunds($user, $order);

            $order->status = OrderStatus::Cancelled;
            $order->save();
        });
    }

    private function validateAndDeductFunds(User $user, array $data): void
    {
        $cost = $data['price'] * $data['amount'];

        if ($data['side'] === 'buy') {
            $this->ensureSufficientBalance($user, $cost);
            $this->deductBalance($user, $cost);
        } else {
            $this->ensureSufficientAsset($user, $data['symbol'], $data['amount']);
            $this->lockAsset($user, $data['symbol'], $data['amount']);
        }
    }

    private function refundFunds(User $user, Order $order): void
    {
        if ($order->side === 'buy') {
            $cost = (float) $order->price * (float) $order->amount;
            $this->refundBalance($user, $cost);
        } else {
            $this->releaseAsset($user, $order->symbol, (float) $order->amount);
        }
    }

    private function ensureSufficientBalance(User $user, float $amount): void
    {
        if ((float) $user->balance < $amount) {
            throw new ClientException('Insufficient USD balance.');
        }
    }

    private function ensureSufficientAsset(User $user, string $symbol, float $amount): void
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        if (! $asset || (float) $asset->amount < $amount) {
            throw new ClientException('Insufficient asset balance.');
        }
    }

    private function deductBalance(User $user, float $amount): void
    {
        $user->balance = (float) $user->balance - $amount;
        $user->save();
    }

    private function lockAsset(User $user, string $symbol, float $amount): void
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        $asset->amount = (float) $asset->amount - $amount;
        $asset->locked_amount = (float) $asset->locked_amount + $amount;
        $asset->save();
    }

    private function refundBalance(User $user, float $amount): void
    {
        $user->balance = (float) $user->balance + $amount;
        $user->save();
    }

    private function releaseAsset(User $user, string $symbol, float $amount): void
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->first();

        if ($asset) {
            $asset->amount = (float) $asset->amount + $amount;
            $asset->locked_amount = (float) $asset->locked_amount - $amount;
            $asset->save();
        }
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    public function match(Order $order)
    {
        $order->refresh();

        if (! $this->canOrderBeMatched($order)) {
            return;
        }

        $matches = $this->findMatchingOrders($order);

        foreach ($matches as $match) {
            if ($order->amount <= 0) {
                break;
            }

            if (! $this->isPriceMatch($order, $match)) {
                break;
            }

            $this->processMatch($order, $match);

            $order->refresh();
        }
    }

    protected function canOrderBeMatched(Order $order): bool
    {
        return $order->status === OrderStatus::Open && $order->amount > 0;
    }

    protected function findMatchingOrders(Order $order)
    {
        $isBuy = $order->side === 'buy';

        return Order::where('symbol', $order->symbol)
            ->where('side', $isBuy ? 'sell' : 'buy')
            ->where('status', OrderStatus::Open)
            ->orderBy('price', $isBuy ? 'asc' : 'desc')
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();
    }

    protected function isPriceMatch(Order $order, Order $match): bool
    {
        $isBuy = $order->side === 'buy';

        if ($isBuy && $match->price > $order->price) {
            return false;
        }

        if (! $isBuy && $match->price < $order->price) {
            return false;
        }

        return true;
    }

    protected function processMatch(Order $order, Order $match): void
    {
        DB::transaction(function () use ($order, $match) {
            // Lock rows
            $order = Order::lockForUpdate()->find($order->id);
            $match = Order::lockForUpdate()->find($match->id);

            if (! $this->canExecuteTrade($order, $match)) {
                return;
            }

            $this->executeTrade($order, $match);
        });
    }

    protected function canExecuteTrade(Order $order, Order $match): bool
    {
        return $order->status === OrderStatus::Open && $match->status === OrderStatus::Open;
    }

    protected function executeTrade(Order $order, Order $match): void
    {
        $tradeAmount = (float) min($order->amount, $match->amount);
        $tradePrice = (float) $match->price;
        $volumeUsd = $tradeAmount * $tradePrice;
        $fee = $volumeUsd * 0.015;

        $isBuy = $order->side === 'buy';

        $this->createTradeRecord($order, $match, $tradePrice, $tradeAmount, $fee, $isBuy);

        $this->updateOrderAmounts($order, $match, $tradeAmount);

        $this->updateBalances($order, $match, $tradePrice, $tradeAmount, $fee, $isBuy);

        event(new OrderMatched($order, $match, $tradeAmount, $tradePrice));
    }

    protected function createTradeRecord(Order $order, Order $match, float $price, float $amount, float $fee, bool $isBuy): void
    {
        $buyer = $isBuy ? $order->user : $match->user;
        $seller = $isBuy ? $match->user : $order->user;

        Trade::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $order->symbol,
            'price' => $price,
            'amount' => $amount,
            'fee' => $fee,
        ]);
    }

    protected function updateOrderAmounts(Order $order, Order $match, float $amount): void
    {
        $order->amount -= $amount;
        $match->amount -= $amount;

        if ($order->amount <= 0) {
            $order->status = OrderStatus::Filled;
        }
        if ($match->amount <= 0) {
            $match->status = OrderStatus::Filled;
        }

        $order->save();
        $match->save();
    }

    protected function updateBalances(Order $order, Order $match, float $tradePrice, float $tradeAmount, float $fee, bool $isBuy): void
    {
        $buyer = $isBuy ? $order->user : $match->user;
        $seller = $isBuy ? $match->user : $order->user;

        $buyerUser = User::lockForUpdate()->find($buyer->id);
        $sellerUser = User::lockForUpdate()->find($seller->id);

        $this->updateBuyerBalance($buyerUser, $order, $match, $tradePrice, $tradeAmount, $fee, $isBuy);
        $this->updateSellerBalance($sellerUser, $order, $tradePrice, $tradeAmount, $fee);

        $this->updateBuyerAsset($buyer, $order->symbol, $tradeAmount);
        $this->updateSellerAsset($seller, $order->symbol, $tradeAmount);

        $buyerUser->save();
        $sellerUser->save();
    }

    protected function updateBuyerBalance(User $buyerUser, Order $order, Order $match, float $tradePrice, float $tradeAmount, float $fee, bool $isBuy): void
    {
        $buyerLockPrice = $isBuy ? $order->price : $match->price;
        $lockedDeduction = $buyerLockPrice * $tradeAmount;

        $cost = $tradePrice * $tradeAmount;
        $totalBuyerSpend = $cost + $fee;
        $surplus = $lockedDeduction - $totalBuyerSpend;

        if ($surplus != 0) {
            $buyerUser->balance += $surplus;
        }
    }

    protected function updateSellerBalance(User $sellerUser, Order $order, float $tradePrice, float $tradeAmount, float $fee): void
    {
        $cost = $tradePrice * $tradeAmount;
        $sellerProceeds = $cost - $fee;
        $sellerUser->balance += $sellerProceeds;
    }

    protected function updateBuyerAsset(User $buyer, string $symbol, float $amount): void
    {
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $symbol],
            ['amount' => 0, 'locked_amount' => 0]
        );
        $buyerAsset->amount += $amount;
        $buyerAsset->save();
    }

    protected function updateSellerAsset(User $seller, string $symbol, float $amount): void
    {
        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $symbol)
            ->first();

        if ($sellerAsset) {
            $sellerAsset->locked_amount -= $amount;
            $sellerAsset->save();
        }
    }
}

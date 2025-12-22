<?php

namespace App\Http\Controllers;

use App\Jobs\MatchOrders;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::where('status', 'open');

        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|gt:0',
            'amount' => 'required|numeric|gt:0',
        ]);

        $user = $request->user();
        $totalCost = $validated['price'] * $validated['amount'];
        $order = null;

        try {
            DB::transaction(function () use ($user, $validated, $totalCost, &$order) {
                // Refresh user for lock
                $user = User::lockForUpdate()->find($user->id);

                if ($validated['side'] === 'buy') {
                    if ($user->balance < $totalCost) {
                        throw new Exception('Insufficient USD balance.');
                    }

                    $user->balance -= $totalCost;
                    $user->save();
                } else {
                    // Sell Order
                    $asset = Asset::lockForUpdate()
                        ->where('user_id', $user->id)
                        ->where('symbol', $validated['symbol'])
                        ->first();

                    if (! $asset || $asset->amount < $validated['amount']) {
                        throw new Exception('Insufficient asset balance.');
                    }

                    $asset->amount -= $validated['amount'];
                    $asset->locked_amount += $validated['amount'];
                    $asset->save();
                }

                $order = $user->orders()->create([
                    'symbol' => $validated['symbol'],
                    'side' => $validated['side'],
                    'price' => $validated['price'],
                    'amount' => $validated['amount'],
                    'status' => 'open',
                ]);
            });
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        // Trigger matching synchronously as per simple requirement, or dispatch job.
        // For "Real-time" and "Atomic", sync is fine for MVP.
        MatchOrders::dispatch($order);

        return response()->json($order, 201);
    }

    public function destroy(string $id, Request $request)
    {
        $order = $request->user()->orders()->where('id', $id)->firstOrFail();

        if ($order->status !== 'open') {
            return response()->json(['message' => 'Order is not open.'], 400);
        }

        DB::transaction(function () use ($order) {
            $user = User::lockForUpdate()->find($order->user_id);
            $order->refresh();
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

        return response()->json(['message' => 'Order cancelled.']);
    }
}

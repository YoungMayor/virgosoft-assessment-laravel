<?php

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Support\Facades\Event;

test('full match execution executes trade and updates balances correctly', function () {
    Event::fake([OrderMatched::class]);

    // Setup User A (Buyer)
    $buyer = User::factory()->create(['balance' => 1000]);

    // Setup User B (Seller)
    $seller = User::factory()->create(['balance' => 0]);
    Asset::create(['user_id' => $seller->id, 'symbol' => 'BTC', 'amount' => 1, 'locked_amount' => 0]);

    // Buyer places Buy Order via Controller (mocks API call effectively)
    // We can call Service directly or Controller. Controller adds validation/locking.
    // Let's call Controller store logic via simulating request or simpler: Create Order and call Service.
    // Simulating Controller ensures integration.

    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => 100,
        'amount' => 1,
    ]);

    //
    $response->assertStatus(201);

    // Check Buyer State
    $buyer->refresh();
    expect($buyer->balance)->toEqual(900); // 1000 - 100 locked
    expect($buyer->orders()->first()->status)->toBe('open');

    // Seller places Sell Order
    $response = $this->actingAs($seller)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => 100,
        'amount' => 1,
    ]);

    $response->assertStatus(201);

    // Matching should have happened (Job dispatched synchronously in test? No, jobs are queued usually.
    // Did I set sync driver? 'QUEUE_CONNECTION=database' in .env.example.
    // In Verify/Test environment, we should process jobs or use sync.
    // I called dispatch().
    // We can use generic MatchingService call directly for verification or assume Job runs if we didn't mock Queue.
    // But better to call Service match manually here to be sure, or rely on dispatch synchronous behaviour if configured.
    // Let's instantiate Service and match.

    $service = new MatchingService;
    // Match logic runs on the *new* order (Seller's order).
    $sellerOrder = $seller->orders()->first();
    $service->match($sellerOrder);

    // Assertions
    $buyer->refresh();
    $seller->refresh();

    // Commission = 1.5% of 100 = 1.5 USD.
    // Buyer Paid 100. Fee is extra 1.5?
    // Logic: surplus = lockedDeduction (100) - (cost (100) + fee (1.5)) = -1.5.
    // Buyer Balance was 900. becomes 900 - 1.5 = 898.5.
    expect($buyer->balance)->toEqual(898.5);

    // Seller Logic: Proceeds = cost (100) - fee (1.5) = 98.5.
    // Seller Balance was 0. Becomes 98.5.
    expect($seller->balance)->toEqual(98.5);

    // Asset Check
    // Buyer should have 1 BTC
    $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', 'BTC')->first();
    expect($buyerAsset->amount)->toEqual(1);

    // Seller should have 0 BTC (Locked 1 -> Sold 1)
    $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', 'BTC')->first();
    expect($sellerAsset->locked_amount)->toEqual(0);
    expect($sellerAsset->amount)->toEqual(0);

    // Order Status
    $this->assertDatabaseHas('orders', ['id' => $buyer->orders()->first()->id, 'status' => 'filled']);
    $this->assertDatabaseHas('orders', ['id' => $sellerOrder->id, 'status' => 'filled']);

    // Events
    Event::assertDispatched(OrderMatched::class);
});

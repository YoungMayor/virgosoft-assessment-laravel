<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    /**
     * List all open orders.
     * Optionally filter by symbol.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Order::where('status', OrderStatus::Open);

        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }

        return OrderResource::collection($query->orderByDesc('created_at')->get());
    }

    /**
     * Create a new order.
     *
     * @return OrderResource|\Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->user(), $request->validated());

        return new OrderResource($order);
    }

    /**
     * Cancel an order.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id, Request $request)
    {
        $this->orderService->cancelOrder($request->user(), (int) $id);

        return response()->json(['message' => 'Order cancelled.']);
    }
}

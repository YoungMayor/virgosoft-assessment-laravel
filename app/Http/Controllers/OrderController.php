<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
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
        $query = Order::where('status', 'open');

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
        try {
            $order = $this->orderService->createOrder($request->user(), $request->validated());

            return new OrderResource($order);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel an order.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id, Request $request)
    {
        try {
            $this->orderService->cancelOrder($request->user(), (int) $id);

            return response()->json(['message' => 'Order cancelled.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

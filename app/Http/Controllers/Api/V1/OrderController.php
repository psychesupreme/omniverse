<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of sales orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['outlet', 'user', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->input('outlet_id'));
        }

        $orders = $query->latest('placed_at')->paginate(15);

        return response()->json($orders);
    }

    /**
     * Display the specified order with line items and product details.
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['outlet', 'user', 'items.product']));
    }

    /**
     * Update the status of a sales order.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,pending,approved,delivered,cancelled'],
        ]);

        try {
            $order->update([
                'status' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order'   => $order->load(['outlet', 'user', 'items.product']),
            ]);
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating order status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

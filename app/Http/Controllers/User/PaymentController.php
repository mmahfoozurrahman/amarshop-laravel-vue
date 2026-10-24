<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * In-app demo gateway actions. Real SSLCommerz callbacks live in Guest\PaymentController.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function demo(Request $request, Order $order, string $result): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($result === 'success') {
            if ($order->isCancelled()) {
                abort(409, 'This order has been cancelled and cannot be paid.');
            }

            $paid = $this->orders->markPaid(
                $order,
                $order->transaction_id ?: $this->orders->generateTransactionId(),
                $order->total,
            );

            return response()->json([
                'message' => $paid ? 'Payment successful.' : 'This order is already paid.',
                'data' => new OrderResource($order->fresh()->load('items.product')),
            ]);
        }

        if ($order->isPaid()) {
            abort(409, 'Paid orders cannot be cancelled.');
        }

        $this->orders->cancelAndRestoreStock($order);

        return response()->json([
            'message' => $result === 'cancel'
                ? 'Payment cancelled. Your order was cancelled and stock restored.'
                : 'Payment failed. Your order was cancelled and stock restored.',
            'data' => new OrderResource($order->fresh()->load('items.product')),
        ]);
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SslcommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly SslcommerzService $gateway,
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $order = $this->orders->placeOrder($request->user(), $request->validated());
        $payment = $this->gateway->init($order->load(['items', 'user']));

        return response()->json([
            'message' => 'Order placed. Please complete the payment.',
            'data' => new OrderResource($order),
            'redirect_url' => $payment['redirect_url'],
            'gateway' => $payment['gateway'],
        ], 201);
    }

    public function pay(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->isPaid()) {
            abort(409, 'This order is already paid.');
        }

        if ($order->isCancelled()) {
            abort(409, 'This order has been cancelled.');
        }

        $payment = $this->gateway->init($order->load(['items', 'user']));

        return response()->json([
            'message' => 'Redirecting to the payment gateway.',
            'redirect_url' => $payment['redirect_url'],
            'gateway' => $payment['gateway'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SslcommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the public SSLCommerz gateway callbacks (success / fail / cancel / IPN).
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly SslcommerzService $gateway,
    ) {}

    public function success(Request $request): RedirectResponse|JsonResponse
    {
        return $this->finalize($request, 'success');
    }

    public function fail(Request $request): RedirectResponse|JsonResponse
    {
        return $this->finalize($request, 'fail');
    }

    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        return $this->finalize($request, 'cancel');
    }

    public function ipn(Request $request): JsonResponse
    {
        $this->finalize($request, 'ipn');

        return response()->json(['message' => 'IPN processed.']);
    }

    private function finalize(Request $request, string $result): RedirectResponse|JsonResponse
    {
        $orderNumber = (string) ($request->input('tran_id') ?: $request->input('order_number'));
        $order = Order::with('items')->where('order_number', $orderNumber)->first();

        if (! $order) {
            if ($result === 'ipn') {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            return redirect('/orders?payment=unknown');
        }

        if ($result === 'success') {
            $paid = $this->gateway->isDemo()
                || $this->validateWithGateway($request, $order);

            if ($paid) {
                $transactionId = (string) ($request->input('bank_tran_id')
                    ?: $request->input('tran_id')
                    ?: $order->transaction_id);

                $this->orders->markPaid($order, $transactionId, $request->input('amount'));
            }

            return $this->backToSpa($order, $paid ? 'success' : 'failed');
        }

        $this->orders->cancelAndRestoreStock($order);

        return $this->backToSpa($order, $result === 'cancel' ? 'cancelled' : 'failed');
    }

    private function validateWithGateway(Request $request, Order $order): bool
    {
        $validationId = (string) $request->input('val_id');

        if ($validationId === '') {
            return false;
        }

        $data = $this->gateway->validate($validationId);

        if (! is_array($data)) {
            return false;
        }

        $status = strtoupper((string) ($data['status'] ?? ''));
        $amount = (float) ($data['amount'] ?? 0);
        $tranId = (string) ($data['tran_id'] ?? '');

        return $status === 'VALID'
            && $tranId === $order->order_number
            && $amount + 0.01 >= (float) $order->total;
    }

    private function backToSpa(Order $order, string $status): RedirectResponse
    {
        return redirect("/orders/{$order->order_number}?payment={$status}");
    }
}

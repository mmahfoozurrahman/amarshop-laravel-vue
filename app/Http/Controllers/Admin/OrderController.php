<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /** Allowed status transitions. */
    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));

        $orders = Order::query()
            ->with(['user', 'items'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('order_number', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return AdminOrderResource::collection($orders);
    }

    public function show(Order $order): AdminOrderResource
    {
        return new AdminOrderResource($order->load(['user', 'items.product']));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = (string) $request->input('status');

        if ($newStatus === $order->status) {
            return response()->json([
                'message' => 'Order status is unchanged.',
                'data' => new AdminOrderResource($order->load(['user', 'items.product'])),
            ]);
        }

        $allowed = self::TRANSITIONS[$order->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            abort(409, "An order cannot move from {$order->status} to {$newStatus}.");
        }

        if ($newStatus === 'cancelled') {
            if ($order->isPaid()) {
                abort(409, 'Paid orders cannot be cancelled from the admin panel.');
            }

            $this->orders->cancelAndRestoreStock($order);

            return response()->json([
                'message' => 'Order cancelled and stock restored.',
                'data' => new AdminOrderResource($order->fresh()->load(['user', 'items.product'])),
            ]);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Order status updated.',
            'data' => new AdminOrderResource($order->fresh()->load(['user', 'items.product'])),
        ]);
    }
}

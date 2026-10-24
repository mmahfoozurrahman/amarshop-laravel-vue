<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;

/** @mixin Order */
class AdminOrderResource extends OrderResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'payment_gateway_page' => $this->payment_gateway_page,
            'stock_restored_at' => $this->stock_restored_at?->toIso8601String(),
        ];
    }
}

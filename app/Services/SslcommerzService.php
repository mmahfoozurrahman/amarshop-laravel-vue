<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslcommerzService
{
    public function mode(): string
    {
        return (string) config('sslcommerz.mode', 'demo');
    }

    public function isDemo(): bool
    {
        return $this->mode() !== 'sandbox' || ! $this->hasCredentials();
    }

    public function hasCredentials(): bool
    {
        return filled(config('sslcommerz.store_id')) && filled(config('sslcommerz.store_password'));
    }

    /**
     * Start a payment session for the order.
     *
     * @return array{gateway: string, redirect_url: string}
     */
    public function init(Order $order): array
    {
        if ($this->isDemo()) {
            // The demo gateway is a Vue Router page, so hand back a *relative* path:
            // the SPA pushes it through the router. An absolute URL would be treated
            // as a relative path by `router.push()` and double the origin.
            return [
                'gateway' => 'demo',
                'redirect_url' => '/payment/demo/'.$order->order_number,
            ];
        }

        $response = Http::asForm()->acceptJson()->post($this->endpoint('init'), $this->payload($order));

        $gatewayUrl = $response->json('GatewayPageURL');

        if (! $response->successful() || ! is_string($gatewayUrl) || $gatewayUrl === '') {
            Log::error('SSLCommerz init failed', [
                'order' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            abort(502, 'Could not start the payment gateway. Please try again.');
        }

        $order->forceFill(['payment_gateway_page' => $gatewayUrl])->save();

        return [
            'gateway' => 'sandbox',
            'redirect_url' => $gatewayUrl,
        ];
    }

    /**
     * Validate a transaction with the SSLCommerz validator API (sandbox/live).
     *
     * @return array<string, mixed>|null
     */
    public function validate(string $validationId): ?array
    {
        if ($this->isDemo()) {
            return null;
        }

        $response = Http::acceptJson()->get($this->endpoint('validate'), [
            'val_id' => $validationId,
            'store_id' => config('sslcommerz.store_id'),
            'store_passwd' => config('sslcommerz.store_password'),
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            Log::warning('SSLCommerz validation failed', [
                'val_id' => $validationId,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Order $order): array
    {
        return [
            'store_id' => config('sslcommerz.store_id'),
            'store_passwd' => config('sslcommerz.store_password'),
            'total_amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => config('sslcommerz.currency', 'BDT'),
            'tran_id' => $order->order_number,
            'success_url' => url('/api/payment/success'),
            'fail_url' => url('/api/payment/fail'),
            'cancel_url' => url('/api/payment/cancel'),
            'ipn_url' => url('/api/payment/ipn'),
            'cus_name' => $order->name,
            'cus_email' => $order->user?->email ?: 'customer@amarshop.test',
            'cus_add1' => $order->address,
            'cus_city' => $order->city,
            'cus_postcode' => $order->postcode ?: '1000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->phone,
            'shipping_method' => 'NO',
            'num_of_item' => $order->items->sum('quantity'),
            'product_name' => 'AmarShop order '.$order->order_number,
            'product_category' => 'ecommerce',
            'product_profile' => 'general',
        ];
    }

    private function endpoint(string $key): string
    {
        $environment = config('sslcommerz.sandbox') ? 'sandbox' : 'live';

        return (string) config("sslcommerz.endpoints.{$environment}.{$key}");
    }
}

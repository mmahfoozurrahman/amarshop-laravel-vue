<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveGuestCart
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $token = $request->header('X-Guest-Token') ?: $request->input('guest_token');

        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9-]{16,64}$/', $token)) {
            abort(422, 'A valid guest cart token is required.');
        }

        $request->attributes->set('guest_token', $token);

        return $next($request);
    }
}

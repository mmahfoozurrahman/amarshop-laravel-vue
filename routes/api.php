<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
| Split by audience so ownership of every endpoint is obvious:
|   guest.php -> public catalogue, guest cart, payment callbacks
|   user.php  -> OTP auth + authenticated customer endpoints
|   admin.php -> password auth + staff management endpoints
*/
require __DIR__.'/api/guest.php';
require __DIR__.'/api/user.php';
require __DIR__.'/api/admin.php';

// Placeholder root so the API is discoverable.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'version' => '1.0.0',
]));

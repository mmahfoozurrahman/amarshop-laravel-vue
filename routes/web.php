<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA shell
|--------------------------------------------------------------------------
| Every non-API route renders the Vue application. Vue Router takes over
| from there and resolves guest, customer and admin pages client side.
*/
Route::view('/{any?}', 'app')
    ->where('any', '^(?!api/|storage/|up$).*$')
    ->name('spa');

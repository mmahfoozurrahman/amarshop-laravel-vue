<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="AmarShop — a simple e-commerce demo built with Laravel, MySQL, Bootstrap and Vue.">
    <title>{{ config('app.name', 'AmarShop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="app-boot d-flex align-items-center justify-content-center min-vh-100">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status" aria-label="Loading"></div>
                <p class="mt-3 text-muted mb-0">Loading AmarShop…</p>
            </div>
        </div>
    </div>
</body>
</html>

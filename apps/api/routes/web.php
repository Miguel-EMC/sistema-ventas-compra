<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'layer' => 'api',
        'status' => 'ok',
        'documentation' => url('/api/v1/health'),
    ]);
});

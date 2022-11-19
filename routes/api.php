<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CurrencyController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('client')->group(function() {
    Route::get('/currency-change-in-period', [CurrencyController::class, 'percentageChangeInPeriod']);
    Route::get('/currency-change-between-dates', [CurrencyController::class, 'percentageChange']);
});

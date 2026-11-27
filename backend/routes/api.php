<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\MasterDataController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [MasterDataController::class, 'categories']);
    Route::get('/locations', [MasterDataController::class, 'locations']);

    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::post('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
});
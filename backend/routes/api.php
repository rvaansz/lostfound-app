<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaimController;
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
    Route::post('/items/{item}/claims', [ClaimController::class, 'store']);
    Route::get('/items/{item}/claims', [ClaimController::class, 'index']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show']);
    Route::post('/claims/{claim}/approve', [ClaimController::class, 'approve']);
    Route::post('/claims/{claim}/reject', [ClaimController::class, 'reject']);
    Route::get('/my/items', [ItemController::class, 'mine']);
    Route::get('/my/claims', [ClaimController::class, 'mine']);
});